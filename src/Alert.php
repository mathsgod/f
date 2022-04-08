<?php

namespace F;

class Alert extends \ArrayObject
{
    public function info($message)
    {
        $this->append([
            "type" => "info",
            "message" => $message
        ]);
    }

    public function success($message)
    {
        $this->append([
            "type" => "success",
            "message" => $message
        ]);
    }

    public function danger($message)
    {
        $this->append([
            "type" => "danger",
            "message" => $message
        ]);
    }

    public function warning($message)
    {
        $this->append([
            "type" => "warning",
            "message" => $message
        ]);
    }
}
