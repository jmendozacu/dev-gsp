<?php

/**
 * Class FyndiqOutput handle module's IO operations
 */
class FyndiqOutput
{

    const JSON_STATUS_SUCCESS = 'success';
    const JSON_STATUS_ERROR = 'error';

    /**
     * Shows HTTP error
     * @param  int $code HTTP Error code
     * @param  string $name HTTP Error name
     * @param  string $message Content message
     * @return bool
     */
    public function showError($code, $name, $message)
    {
        $this->header(sprintf('HTTP/1.0 %d %s', $code, $name));
        return $this->output($message);
    }

    /**
     * Prints JSON encoded data to the output
     *
     * @param  mixed $data data to print
     * @return bool
     */
    public function outputJSON($data)
    {
        $this->header('Content-Type: application/json');
        return $this->output(json_encode($data));
    }

    /**
     * Returns wrapped JSON data
     *
     * @param  mixed $data data to output
     * @return bool
     */
    public function renderJSON($data)
    {
        return $this->outputJSON(array(
            'fm-service-status' => self::JSON_STATUS_SUCCESS,
            'data' => $data
        ));
    }

    /**
     * create a error to be send back to client.
     *
     * @param $title Error title
     * @param $message $error message
     */
    public function responseError($title, $message)
    {
        $this->outputJSON(array(
            'fm-service-status' => self::JSON_STATUS_ERROR,
            'title' => $title,
            'message' => $message,
        ));
        return null;
    }

    /**
     * Sets HTTP header
     * @param  string $content Content of the header
     */
    public function header($content)
    {
        header($content);
    }

    /**
     * Outputs data to the client
     * @param  string $output Data to be streamed
     * @return bool
     */
    public function output($data)
    {
        echo $data;
        return true;
    }

    /**
     * Stream file to the client
     *
     * @param  resource $file File handler
     * @param  string $fileName File name
     * @param  string $contentType File's content type
     * @param  int $size Size of the content in bytes
     * @return bool
     */
    public function streamFile($file, $fileName, $contentType, $size)
    {
        $this->header('Content-Type: ' . $contentType);
        $this->header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $this->header('Content-Transfer-Encoding: binary');
        $this->header('Content-Length: ' . $size);
        $this->header('Expires: 0');
        rewind($file);
        if(function_exists('fpassthru') && !in_array('fpassthru', array_map('trim', explode(', ', @ini_get('disable_functions'))))) {
            return fpassthru($file);
        }
        flock($file, LOCK_SH);
        $out=fread($file,$size);
        echo $out;
        flock($file, LOCK_UN);
        return strlen($out);
    }

    /**
     * Flushes content and closes connection
     *
     * @param  string $message Message to flush
     * @return bool
     */
    public function flushHeader($message)
    {
        // Adapted from: http://stackoverflow.com/questions/138374/close-a-connection-early
        if (ob_get_length()) {
            ob_end_clean();
        }
        $this->header('Connection: close');
        ignore_user_abort(true);
        ob_start();
        $this->output($message);
        $size = ob_get_length();
        $this->header('Content-Length: ' . $size);
        ob_end_flush();
        flush();
        return true;
    }
}
