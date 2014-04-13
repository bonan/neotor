<?php

$timer = Array();
class Timer
{
    static function add($name, $time, $cmd)
    {
        global $timer;
        $timer[$name] = Array(
            'name' => $name,
            'time' => (time()+$time),
            'cmd'  => $cmd);
        return true;
    }
    static function add2($name, $time, $method, $params = Array())
    {
        global $timer;
        $timer[$name] = Array(
            'name'   => $name,
            'time'   => (time()+$time),
            'method' => $method,
            'params' => $params
        );
        return true;
    }
    static function del($name)
    {
        global $timer;
        if (isset($timer[$name])) {
            unset($timer[$name]);
            return true;
        } else {
            return false;
        }
    }

    static function checkTimers()
    {
        global $timer;
        foreach ($timer as $key=>$value) {
            if ($value['time'] < time()) {
                $temp = $timer[$key];
                unset($timer[$key]);
                if (isset($temp['cmd'])) {
                    eval($temp['cmd']);
                } elseif (isset($temp['method'])) {
                    call_user_func_array($temp['method'], $temp['params']);
                }
                unset($temp);
            }
        }
        return true;
    }
}

?>
