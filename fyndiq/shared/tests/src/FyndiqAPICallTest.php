<?php

class FyndiqAPICallTest extends PHPUnit_Framework_TestCase
{

    /**
     * @return array
     */
    public function testCallApiRawProvider()
    {
        return array(
            array(
                'Throws FyndiqAPINoAPIClass when wrong caller is passed',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                false,
                'FyndiqAPINoAPIClass',
                false
            ),
            array(
                'Throws FyndiqAPIPageNotFound when status HTTP_ERROR_DEFAULT is returned',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                function () {
                    return array(
                        'status' => FyndiqAPICall::HTTP_ERROR_DEFAULT
                    );
                },
                'FyndiqAPIPageNotFound',
                false
            ),
            array(
                'Throws FyndiqAPIAuthorizationFailed when status HTTP_ERROR_UNAUTHORIZED is returned',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                function () {
                    return array(
                        'status' => FyndiqAPICall::HTTP_ERROR_UNAUTHORIZED
                    );
                },
                'FyndiqAPIAuthorizationFailed',
                false
            ),
            array(
                'Throws FyndiqAPITooManyRequests when status HTTP_ERROR_TOOMANY is returned',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                function () {
                    return array(
                        'status' => FyndiqAPICall::HTTP_ERROR_TOOMANY
                    );
                },
                'FyndiqAPITooManyRequests',
                false
            ),
            array(
                'Throws FyndiqAPIServerError when status HTTP_ERROR_SERVER is returned',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                function () {
                    return array(
                        'status' => FyndiqAPICall::HTTP_ERROR_SERVER,
                    );
                },
                'FyndiqAPIServerError',
                false
            ),
            array(
                'Throws FyndiqAPIServerError when status HTTP_ERROR_SERVER is returned with one error',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                function () {
                    $data = new stdClass();
                    $data->error_messages = 'ERROR';
                    return array(
                        'status' => FyndiqAPICall::HTTP_ERROR_CUSTOM,
                        'data' => $data
                    );
                },
                'FyndiqAPIBadRequest',
                false
            ),
            array(
                'Throws FyndiqAPIServerError when status HTTP_ERROR_SERVER is returned with multiple errors',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                function () {
                    $data = new stdClass();
                    $data->error_messages = array('ERROR1', 'ERROR2');
                    return array(
                        'status' => FyndiqAPICall::HTTP_ERROR_CUSTOM,
                        'data' => $data
                    );
                },
                'FyndiqAPIBadRequest',
                false
            ),
            array(
                'Throws FyndiqAPIDataInvalid when last json_decode failed',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                function () {
                    @json_decode('}}{{');
                },
                'FyndiqAPIDataInvalid',
                false
            ),
            array(
                'Throws FyndiqAPIUnsupportedStatus when last json_decode failed',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                function () {
                    // Clear last json_decode error
                    json_decode('{}');
                    return array(
                        'status' => 666,
                    );
                },
                'FyndiqAPIUnsupportedStatus',
                false
            ),
            array(
                'Doesn\'t throw error if everything is awesome',
                'test-agent',
                'user',
                'token',
                'method',
                'path',
                array(),
                function () {
                    return array(
                        'status' => FyndiqAPICall::HTTP_SUCCESS_DEFAULT,
                        'data' => 'awesome'
                    );
                },
                '',
                array(
                    'status' => FyndiqAPICall::HTTP_SUCCESS_DEFAULT,
                    'data' => 'awesome'
                )
            ),
        );
    }

    /**
     * @param $userAgent
     * @param $username
     * @param $apiToken
     * @param $method
     * @param $path
     * @param $data
     * @param $caller
     * @param $exception
     * @param $expected
     * @throws FyndiqAPIAuthorizationFailed
     * @throws FyndiqAPIBadRequest
     * @throws FyndiqAPIDataInvalid
     * @throws FyndiqAPINoAPIClass
     * @throws FyndiqAPIPageNotFound
     * @throws FyndiqAPIServerError
     * @throws FyndiqAPITooManyRequests
     * @throws FyndiqAPIUnsupportedStatus
     *
     * @dataProvider testCallApiRawProvider
     */
    public function testCallApiRaw(
        $message,
        $userAgent,
        $username,
        $apiToken,
        $method,
        $path,
        $data,
        $caller,
        $exception,
        $expected
    ) {
        if ($exception) {
            $this->setExpectedException($exception);
        }
        $response = FyndiqAPICall::callApiRaw($userAgent, $username, $apiToken, $method, $path, $data, $caller);
        if (!$exception) {
            $this->assertEquals($expected, $response, $message);
        }
    }
}
