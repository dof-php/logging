<?php

declare(strict_types=1);

namespace DOF\Logging\Logger;

use DOF\Util\Str;
use DOF\Util\F;
use DOF\Util\IS;
use DOF\Util\FS;
use DOF\Util\Arr;
use DOF\Util\JSON;
use DOF\Util\Format;
use DOF\Logging\LoggerInterface;
use DOF\Logging\LoggerTrait;

class File implements LoggerInterface
{
    use LoggerTrait;

    const LOG_PREFX = 'log';
    const LOG_DEBUG = 'debug';
    const LOG_ERROR = 'DOF_FILE_LOGGING_SOS';
    const LOG_SINGLE = 'single';

    /** @var string: Where Log files will be stored */
    private $directory;

    /** @var string: The dirname for achieving log files */
    private $archive =  'archive';

    /** @var int: The filesize limit of live log file */
    private $filesize = 4194304;    // 4M (default)

    /** @var string: The suffix of log file */
    private $suffix = 'log';

    /** @var string: The postfix of log path */
    private $postfix;

    /** @var mixed: Debug mode and debug key used to name log file */
    private $debug = null;

    /** @var boolean: Logging into one single file or not */
    private $single = false;

    /** @var boolean: Echo sos log details or not when file or directory unwritable */
    private $sos = true;

    /** @var string: Log level keyword */
    private $level;

    /** @var string: Separator of each single log text */
    private $separator = PHP_EOL;

    /** @var string: Server API of current PHP process using */
    private $sapi = PHP_SAPI;

    public function log($level, $message, array $context = [])
    {
        $microtime = \microtime(true);

        $this->level = \strtoupper(Format::string($level) ?: 'log');

        // Hard-code an fixed order of index array to shorten log text
        $log = [
            Format::microtime('T Y-m-d H:i:s', '.', $microtime),
            $this->level,
            $message,
            Arr::mask($context),
        ];
        if (! $this->directory) {
            return $this->sos('DIRECTORY_MISSING', $log);
        }

        $fpid  = $this->single ? File::LOG_SINGLE : \getmypid();
        $user  = F::phpuser();

        $path = $this->postfix ? [File::LOG_PREFX, $user, $this->postfix] : [File::LOG_PREFX, $user];
        $path = FS::path($this->directory, \join('-', $path));
        if ((false === FS::mkdir($path)) || (! \is_writable($path))) {
            return $this->sos('PERMISSION_DENIED_DIR', $log);
        }

        $file = FS::path($path, \join('.', \is_null($this->debug) ? [
            $this->level, $this->sapi, $user, $fpid, $this->suffix
        ] : [
            \join('-', [$this->level, (\is_string($this->debug) ? $this->debug : FILE::LOG_DEBUG)]), $this->sapi, $user, $fpid, $this->suffix
        ]));
        if (\is_file($file) && (\filesize($file) >= $this->filesize)) {
            $archive = FS::mkdir(
                $path,
                $this->archive,
                \date('Y', $microtime),
                \date('m', $microtime),
                \date('d', $microtime),
                $user,
                $this->level,
                $this->sapi
            );

            rename($file, FS::path($archive, \join('.', [
                Format::microtime('Ymd-His', '-', $microtime),
                $user,
                $this->level,
                $this->sapi,
                $fpid,
                $this->suffix
            ])));
        }

        $this->save(JSON::encode($log), $file);
    }

    public function save(string $log, string $file)
    {
        $fp = \fopen($file, 'a+');
        if (false !== $fp) {
            // stream_set_blocking($fp, 0);
            // if (flock($fp, LOCK_EX)) {
            \fwrite($fp, $log);
            if (! \is_null($this->separator)) {
                \fwrite($fp, $this->separator);
            }
            // }
            // flock($fp, LOCK_UN);
            \fclose($fp);

        // \file_put_contents($file, $log.$this->separator, FILE_APPEND | LOCK_EX);
            // error_log($log, 3, $file);
        } else {
            $this->sos('PERMISSION_DENIED_FILE', $log);
        }
    }

    private function sos(string $message, $log)
    {
        if ($this->sos) {
            echo JSON::pretty([\join(':', [File::LOG_ERROR, $message]) => $log]), $this->separator;
            return;
        }

        echo \join(':', [File::LOG_ERROR, $message]), $this->separator;
    }

    /**
     * Setter for directory
     *
     * @param string $directory
     * @return File
     */
    public function setDirectory(string $directory)
    {
        if ($dir = \trim($directory)) {
            $this->directory = $directory;
        }
    
        return $this;
    }

    /**
     * Setter for postfix
     *
     * @param string $postfix
     * @return File
     */
    public function setPostfix(string $postfix)
    {
        if ($postfix = \trim($postfix)) {
            $this->postfix = $postfix;
        }
    
        return $this;
    }

    /**
     * Setter for suffix
     *
     * @param string $suffix
     * @return File
     */
    public function setSuffix(string $suffix)
    {
        if ($suffix = \trim($suffix)) {
            $this->suffix = $suffix;
        }
    
        return $this;
    }

    /**
     * Setter for archive
     *
     * @param string $archive
     * @return File
     */
    public function setArchive(string $archive)
    {
        if ($archive = \trim($archive)) {
            $this->archive = $archive;
        }
    
        return $this;
    }

    /**
     * Setter for filesize
     *
     * @param int $filesize
     * @return File
     */
    public function setFilesize(int $filesize)
    {
        if ($filesize > 0) {
            $this->filesize = $filesize;
        }
    
        return $this;
    }

    /**
     * Setter for separator
     *
     * @param string $separator
     * @return File
     */
    public function setSeparator(string $separator)
    {
        $this->separator = $separator;
    
        return $this;
    }

    /**
     * Setter for debug
     *
     * @param string $debug
     * @return File
     */
    public function setDebug(string $debug)
    {
        $this->debug = $debug;
    
        return $this;
    }

    /**
     * Setter for single
     *
     * @param mixed $single
     * @return File
     */
    public function setSingle($single)
    {
        $this->single = IS::confirm($single);
    
        return $this;
    }

    /**
     * Setter for sos
     *
     * @param bool $sos
     * @return File
     */
    public function setSOS(bool $sos)
    {
        $this->sos = $sos;
    
        return $this;
    }

    /**
     * Setter for sapi
     *
     * @param string $sapi
     * @return File
     */
    public function setSapi(string $sapi)
    {
        $this->sapi = $sapi;
    
        return $this;
    }
}
