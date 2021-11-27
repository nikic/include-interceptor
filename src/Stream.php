<?php
/**
 * @license    http://www.opensource.org/licenses/mit-license.html
 *
 * Based on https://github.com/antecedent/patchwork/blob/master/Patchwork.php
 */

namespace Nikic\IncludeInterceptor;

class Stream {
    const STREAM_OPEN_FOR_INCLUDE = 128;

    /**
     * @var Interceptor
     */
    private static $defaultInterceptor;

    public static function hasInterceptor(): bool {
        return self::$defaultInterceptor instanceof Interceptor;
    }

    public static function setInterceptor(Interceptor $interceptor): void {
        self::$defaultInterceptor = $interceptor;
    }

    public static function clearInterceptor(): void {
        self::$defaultInterceptor = null;
    }

    /**
     * @var resource
     */
    public $context;

    /**
     * @var resource
     */
    public $resource;

    /**
     * @param callable $callback
     * @return mixed
     */
    private function runUnwrapped($callback) {
        self::$defaultInterceptor->unwrap();
        try {
            $result = $callback(self::$defaultInterceptor);
        } finally {
            self::$defaultInterceptor->wrap();
        }
        return $result;
    }

    /**
     * Determine file which called stream_open() based on backtrace.
     */
    private function getCallingFile(array $backtrace): ?string {
        foreach ($backtrace as $call) {
            if (isset($call['file'])) {
                return $call['file'];
            }
        }
        return null;
    }

    /**
     * Check if the path is relative to the file that included it.
     */
    private function fixPath(string $path, array $backtrace): string {
        if ($path[0] === '/') {
            return $path;
        }
        $callerDir = dirname($this->getCallingFile($backtrace));
        $pathFromCallerContext = $callerDir . '/' . $path;
        if (file_exists($pathFromCallerContext)) {
            return $pathFromCallerContext;
        } else {
            return $path;
        }
    }

    /**
     * For phar:// streams the realpath() operation is not supported, so manually
     * resolve ./ and ../ segments, so that filtering code doesn't have to deal
     * with it.
     *
     * Returns null if the file does not exist.
     */
    private function realpath(string $path): ?string {
        if (($realPath = realpath($path)) !== false) {
            return $realPath;
        }

        // Implementation based on https://github.com/UnionOfRAD/lithium/blob/master/core/Libraries.php.
        if (!preg_match('%^phar://(.+\.phar(?:\.gz)?)(.+)%', $path, $pathComponents)) {
            return null;
        }
        list(, $relativePath, $pharPath) = $pathComponents;

        $pharPath = implode('/', array_reduce(explode('/', $pharPath), function ($parts, $value) {
            if ($value === '..') {
                array_pop($parts);
            } elseif ($value !== '.') {
                $parts[] = $value;
            }
            return $parts;
        }));

        if (($resolvedPath = realpath($relativePath)) !== false) {
            if (file_exists($realPath = "phar://{$resolvedPath}{$pharPath}")) {
                return $realPath;
            }
        }
        return null;
    }

    public function stream_open($path, $mode, $options) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        return $this->runUnwrapped(function (Interceptor $interceptor) use ($path, $mode, $options, $backtrace) {
            $path = $this->fixPath($path, $backtrace);

            $including = (bool)($options & self::STREAM_OPEN_FOR_INCLUDE);
            if ($including) {
                $realPath = $this->realpath($path);
                if ($realPath !== null) {
                    $this->resource = $interceptor->intercept($realPath);
                    if ($this->resource !== null) {
                        return true;
                    }
                }
            }

            if (isset($this->context)) {
                $this->resource = fopen($path, $mode, $options, $this->context);
            } else {
                $this->resource = fopen($path, $mode, $options);
            }
            return $this->resource !== false;
        });
    }

    public function stream_close() {
        return fclose($this->resource);
    }

    public function stream_eof() {
        return feof($this->resource);
    }

    public function stream_flush() {
        return fflush($this->resource);
    }

    public function stream_read($count) {
        return fread($this->resource, $count);
    }

    public function stream_seek($offset, $whence = SEEK_SET) {
        return fseek($this->resource, $offset, $whence) === 0;
    }

    public function stream_stat() {
        return fstat($this->resource);
    }

    public function stream_tell() {
        return ftell($this->resource);
    }

    public function url_stat($path, $flags) {
        return $this->runUnwrapped(function () use ($path, $flags) {
            if ($flags & STREAM_URL_STAT_QUIET) {
                set_error_handler(function () {
                });
            }
            $result = stat($path);
            if ($flags & STREAM_URL_STAT_QUIET) {
                restore_error_handler();
            }
            return $result;
        });
    }

    public function dir_closedir() {
        closedir($this->resource);
        return true;
    }

    public function dir_opendir($path) {
        return $this->runUnwrapped(function () use ($path) {
            if (isset($this->context)) {
                $this->resource = opendir($path, $this->context);
            } else {
                $this->resource = opendir($path);
            }
            return $this->resource !== false;
        });
    }

    public function dir_readdir() {
        return readdir($this->resource);
    }

    public function dir_rewinddir() {
        rewinddir($this->resource);
        return true;
    }

    public function mkdir($path, $mode, $options) {
        return $this->runUnwrapped(function () use ($path, $mode, $options) {
            return mkdir($path, $mode, $options, $this->context);
        });
    }

    public function rename($pathFrom, $pathTo) {
        return $this->runUnwrapped(function () use ($pathFrom, $pathTo) {
            return rename($pathFrom, $pathTo, $this->context);
        });
    }

    public function rmdir($path) {
        return $this->runUnwrapped(function () use ($path) {
            return rmdir($path, $this->context);
        });
    }

    public function stream_cast() {
        return $this->resource;
    }

    public function stream_lock($operation) {
        return flock($this->resource, $operation);
    }

    public function stream_set_option($option, $arg1, $arg2) {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                return stream_set_blocking($this->resource, $arg1);
            case STREAM_OPTION_READ_TIMEOUT:
                return stream_set_timeout($this->resource, $arg1, $arg2);
            case STREAM_OPTION_WRITE_BUFFER:
                return stream_set_write_buffer($this->resource, $arg1);
            case STREAM_OPTION_READ_BUFFER:
                return stream_set_read_buffer($this->resource, $arg1);
            default:
                throw new \InvalidArgumentException();
        }
    }

    public function stream_write($data) {
        return fwrite($this->resource, $data);
    }

    public function unlink($path) {
        return $this->runUnwrapped(function () use ($path) {
            return unlink($path, $this->context);
        });
    }

    public function stream_metadata($path, $option, $value) {
        return $this->runUnwrapped(function () use ($path, $option, $value) {
            switch ($option) {
                case STREAM_META_TOUCH:
                    if (empty($value)) {
                        $result = touch($path);
                    } else {
                        $result = touch($path, $value[0], $value[1]);
                    }
                    break;
                case STREAM_META_OWNER_NAME:
                case STREAM_META_OWNER:
                    $result = chown($path, $value);
                    break;
                case STREAM_META_GROUP_NAME:
                case STREAM_META_GROUP:
                    $result = chgrp($path, $value);
                    break;
                case STREAM_META_ACCESS:
                    $result = chmod($path, $value);
                    break;
                default:
                    throw new \InvalidArgumentException();
            }
            return $result;
        });
    }

    public function stream_truncate($new_size) {
        return ftruncate($this->resource, $new_size);
    }
}
