<?php

require_once '../source/TCPServ.php';
ini_set('error_reporting', E_ALL);
/*class testTcpServ extends PHPUnit_Framework_TestCase
{
    public function testInstantiation()
    {
        $TCPServ = new TCPServ();
    }

}*/

class ExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException SocketException
     */
    public function testBindWithoutParameters()
    {
        $TCPServ = new TCPServ();
        $TCPServ->bind();
    }

    /**
     * @expectedException        SocketException
     * @expectedExceptionMessage Binding to a port less than 1024 requires root privileges.
     */
    public function testBindWithLowPort()
    {
        $TCPServ = new TCPServ();
        $TCPServ->bind($ip = '127.0.0.1', $port = 1000);
    }
}
