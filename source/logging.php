<?php

define('L_SYSTEM', $l = 1);
define('L_ERROR', $l*=2);
define('L_DEBUG', $l*=2);
define('L_IRCALL', $l*=2);
define('L_IRCNUM', $l*=2);
define('L_IRCNOTICE', $l*=2);
define('L_PRIVMSG', $l*=2);
define('L_TELNET', $l*=2);
define('L_PARTYLINE', $l*=2);
define('L_MODULE', $l*=2);
define('L_CHANNEL', $l*=2);
/* define('', $l=$l*2); */

function logWrite($level, $data, $channels = '')
{
    global $log;
    if (!is_array($log)) {
        $log = Array();
    }
    foreach ($log as $key => $value) {
        if ($key>0&&matchCallback($level, $log[$key]->callback)) {
            if (empty($channels)) {
                $write = true;
            } elseif (is_array($channels)) {
                foreach ($channels as $value) {
                    if (in_array($value, $log[$key]->channels)) {
                        $write = true;
                    }
                }
            } else {
                if (in_array($channels, $log[$key]->channels)) {
                    $write = true;
                }
            }

            if (isset($write) && $write == true) {
                $log[$key]->write($data, $channels);
            }
        }
    }
}

function logOpen($logfile, $callback, $channels='')
{
    global $log;
    if (empty($log[0])) {
        $log[0] = 0;
    }
    if ($log[++$log[0]] = new logfile($log[0], $logfile, $callback, $channels)) {
        return true;
    } else {
        return false;
    }
}

class LogFile
{
    var $file;
    var $socket;
    var $channels = Array();
    var $id;

    function logfile($id, $file, $callback, $channels='')
    {
        global $log;
        if (!file_exists($file) && strtolower($file) != 'php://stdout') {
            if (!@touch($file)) {
                return false;
            }
        }
        if (!is_writeable($file) && strtolower($file) != 'php://stdout') {
            return false;
        }
        $this->file = $file;

        if (!$this->socket = @fopen($file, 'a')) {
            if (!$this->socket = @fopen($file, 'w')) {
                return false;
            }
        }
        $this->log = Array();
        $this->callback = $callback;
        if (!is_array($channels)) {
            $this->channels = Array($channels);
        } else {
            $this->channels = $channels;
        }
        $this->id = $id;
        $write = sprintf(
            "-- Log %s opened at %s --\n", 
            $this->file, date('Y-m-d H:i:s')
        );
        fwrite($this->socket, $write, strlen($write));
    }
    function write($data, $channels = '')
    {
        $write = timestamp();
        $write .= $data;
        $write .= "\n";
        fwrite($this->socket, $write, strlen($write));
        return true;
    }

    function close()
    {
        $write = sprintf(
            "-- Log %s closed at %s --\n", 
            $this->file, date('Y-m-d H:i:s')
        );
        fwrite($this->socket, $write, strlen($write));
        fclose($this->socket);
        global $log;
        unset($log[$this->id]);
        return true;
    }

}


?>
