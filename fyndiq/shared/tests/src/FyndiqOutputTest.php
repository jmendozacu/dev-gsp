<?php

class FyndiqOutputTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $this->fmOutput = $this->getMockBuilder('FyndiqOutput')
            ->setMethods(array('output', 'header'))
            ->getMock();
    }

    public function testShowError()
    {
        $code = 100;
        $name = 'name';
        $message = 'message';

        $this->fmOutput->expects($this->once())
            ->method('header')
            ->with(
                $this->equalTo('HTTP/1.0 100 name')
            );
        $this->fmOutput->expects($this->once())
            ->method('output')
            ->with(
                $this->equalTo($message)
            )
            ->willReturn(true);

        $result = $this->fmOutput->showError($code, $name, $message);
        $this->assertTrue($result);
    }

    public function testRenderJSON()
    {
        $data = 'message';

        $this->fmOutput->expects($this->once())
            ->method('header')
            ->with(
                $this->equalTo('Content-Type: application/json')
            );
        $this->fmOutput->expects($this->once())
            ->method('output')
            ->with(
                $this->equalTo('{"fm-service-status":"success","data":"message"}')
            )
            ->willReturn(true);

        $result = $this->fmOutput->renderJSON($data);
        $this->assertTrue($result);
    }

    public function testResponseError()
    {
        $title = 'title';
        $message = 'message';

        $this->fmOutput->expects($this->once())
            ->method('header')
            ->with(
                $this->equalTo('Content-Type: application/json')
            );
        $this->fmOutput->expects($this->once())
            ->method('output')
            ->with(
                $this->equalTo('{"fm-service-status":"error","title":"title","message":"message"}')
            )
            ->willReturn(true);

        $result = $this->fmOutput->responseError($title, $message);
        $this->assertNull($result);
    }
}
