<?php

/**
 * Class FyndiqAPINoAPIClass thrown where FyndiqAPI cannot be found
 */
class FyndiqAPINoAPIClass extends Exception
{
}

/**
 * Class FyndiqAPIDataInvalid thrown when server data was invalid
 */
class FyndiqAPIDataInvalid extends Exception
{
}

/**
 * Class FyndiqAPIPageNotFound thrown when resource is not dound
 */
class FyndiqAPIPageNotFound extends Exception
{
}

/**
 * Class FyndiqAPIAuthorizationFailed thrown when client authorization failed
 */
class FyndiqAPIAuthorizationFailed extends Exception
{
}

/**
 * Class FyndiqAPITooManyRequests thrown when client is sending too many requests
 */
class FyndiqAPITooManyRequests extends Exception
{
}

/**
 * Class FyndiqAPIServerError thrown on server error
 */
class FyndiqAPIServerError extends Exception
{
}

/**
 * Class FyndiqAPIBadRequest thrown when bad request data was send to the server
 */
class FyndiqAPIBadRequest extends Exception
{
}

/**
 * Class FyndiqAPIUnsupportedStatus thrown when the server returned http status which the client doesn't know
 * how to handle
 */
class FyndiqAPIUnsupportedStatus extends Exception
{
}

/**
 * Class FyndiqAPICall is used to make calls to the Fyndiq API
 */
class FyndiqAPICall
{

    const HTTP_SUCCESS_DEFAULT = 200;
    const HTTP_SUCCESS_CREATED = 201;
    const HTTP_SUCCESS_NONCONTENT = 204;
    const HTTP_ERROR_DEFAULT = 404;
    const HTTP_ERROR_UNAUTHORIZED = 401;
    const HTTP_ERROR_TOOMANY = 429;
    const HTTP_ERROR_SERVER = 500;
    const HTTP_ERROR_CUSTOM = 400;

    /**
     * List of custom API errors
     *
     * @var array
     */
    public static $errorMessages = array();


    /**
     * Extracts the custom error messages from the response data
     *
     * @param object $data
     * @return array error messages
     */
    private static function getCustomErrorMessages($data)
    {
        $errorMessages = array();
        // if there are any error messages, save them to class static member
        if (property_exists($data, 'error_messages')) {
            if (is_array($data->error_messages)) {
                // if it contains several messages as an array
                return  $data->error_messages;
            }
            $errorMessages[] = $errorMessages;
        }
        return $errorMessages;
    }

    /**
     * Make API call
     *
     * @param string $userAgent module's user agent
     * @param string $username fyndiq username
     * @param string $apiToken fyndiq api token
     * @param string $method HTTP method to use
     * @param string $path resource path
     * @param array $data request data
     * @param callable $caller optional api caller function
     * @return mixed
     * @throws FyndiqAPIAuthorizationFailed
     * @throws FyndiqAPIBadRequest
     * @throws FyndiqAPIDataInvalid
     * @throws FyndiqAPINoAPIClass
     * @throws FyndiqAPIPageNotFound
     * @throws FyndiqAPIServerError
     * @throws FyndiqAPITooManyRequests
     * @throws FyndiqAPIUnsupportedStatus
     */
    public static function callApiRaw($userAgent, $username, $apiToken, $method, $path, $data = array(), $caller = null)
    {
        if (!is_callable($caller)) {
            if (!class_exists('FyndiqAPI')) {
                throw new FyndiqAPINoAPIClass('No suitable Fyndiq API client found');
            }
            $caller = array('FyndiqAPI', 'call');
        }
        if (version_compare(phpversion(), '5.4.0', '<')) {
            $response = FyndiqAPI::call($userAgent, $username, $apiToken, $method, $path, $data);
        } else {
            $response = $caller($userAgent, $username, $apiToken, $method, $path, $data);
        }

        switch (intval($response['status'])) {
            case self::HTTP_ERROR_DEFAULT:
                throw new FyndiqAPIPageNotFound('Not Found: ' . $path);
            case self::HTTP_ERROR_UNAUTHORIZED:
                throw new FyndiqAPIAuthorizationFailed('Unauthorized');
            case self::HTTP_ERROR_TOOMANY:
                throw new FyndiqAPITooManyRequests('Too Many Requests');
            case self::HTTP_ERROR_SERVER:
                throw new FyndiqAPIServerError('Server Error');
            case self::HTTP_ERROR_CUSTOM:
                self::$errorMessages = self::getCustomErrorMessages($response['data']);
                throw new FyndiqAPIBadRequest('Bad Request');
        }

        // if json_decode failed
        if (function_exists('json_last_error') && json_last_error() != JSON_ERROR_NONE) {
            throw new FyndiqAPIDataInvalid('Error in response data');
        }

        $successHttpStatuses = array(
            self::HTTP_SUCCESS_DEFAULT,
            self::HTTP_SUCCESS_CREATED,
            self::HTTP_SUCCESS_NONCONTENT
        );

        if (!in_array($response['status'], $successHttpStatuses)) {
            throw new FyndiqAPIUnsupportedStatus('Unsupported HTTP status: ' . $response['status']);
        }

        return $response;
    }
}
