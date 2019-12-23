<?php

declare(strict_types=1);

namespace DOF\Logging;

trait LoggerTrait
{
    public function emergency($message, array $context = [])
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    public function exception($message, array $context = [])
    {
        $this->log('exception', $message, $context);
    }

    public function trace($message, array $context = [])
    {
        $this->log('trace', $message, $context);
    }

    public function exceptor($message, array $context = [])
    {
        $this->log('exceptor', $message, $context);
    }

    abstract public function log(string $level, $message, array $context = []);

    public function __call(string $method, array $params = [])
    {
        $this->setDebug($method);

        $this->log('log', $method, $params);
    }
}
