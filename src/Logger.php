<?php

namespace msb\Mongo;


class Logger
{
    private $log_path = '/tmp/mongodb/';


    private function getFile()
    {
        $path = $this->log_path . date('Ymd') . '/';
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        return $path . date('H') . '.log';
    }

    public function error($msg)
    {
        $this->record($msg, __FUNCTION__);
    }

    public function warning($msg)
    {
        $this->record($msg, __FUNCTION__);
    }

    public function info($msg)
    {
        $this->record($msg, __FUNCTION__);
    }

    public function record($msg, $type = 'info')
    {
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        $msg = date('Y-m-d H:i:s') . "\t" . "[$type]\t" . $msg . PHP_EOL;
        file_put_contents($this->getFile(), $msg, FILE_APPEND);
    }
}