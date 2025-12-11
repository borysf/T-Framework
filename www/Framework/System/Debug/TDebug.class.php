<?php
namespace System\Debug;

use Error;
use Exception;
use System\Http\Response\THttpResponse;
use System\TApplication;
use Throwable;

class TDebug {
    private static float $startTime = 0;
    private static array $stack = [];
    private static bool $enabled = true;

    public static function initialize() : void {
        self::$startTime = microtime(true);
    }

    public static function disable() : void {
        self::$enabled = false;
    }

    public static function enable() : void {
        self::$enabled = true;
    }

    public static function log(...$args) : void {
        if (TApplication::isDevelopment()) {
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            self::$stack[] = ['console.log', $stack, microtime(true), self::_map($args)];
        }
    }

    public static function error(...$args) : void {
        if (TApplication::isDevelopment()) {
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            self::$stack[] = ['console.error', $stack, microtime(true), self::_map($args)];
        }
    }

    public static function info(...$args) : void {
        if (TApplication::isDevelopment()) {
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            self::$stack[] = ['console.info', $stack, microtime(true), self::_map($args)];
        }
    }

    public static function warn(...$args) : void {
        if (TApplication::isDevelopment()) {
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            self::$stack[] = ['console.warn', $stack, microtime(true), self::_map($args)];
        }
    }

    public static function memory(string ...$args) {
        if (TApplication::isDevelopment()) {
            array_unshift($args, '[Memory]');

            $args[] = [
                'memory used' => self::_bytesHumanReadable(memory_get_usage()),
                'memory used (peak)' => self::_bytesHumanReadable(memory_get_peak_usage()),
            ];
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            self::$stack[] = ['console.info', $stack, microtime(true), self::_map($args)];
        }
    }

    private static function _map(array $args) : array {
        return array_map(function ($v) {
            if ($v instanceof Throwable) {
                return json_encode([
                    get_class($v) => [
                        'code' => $v->getCode(),
                        'message' => $v->getMessage(),
                        'file' => $v->getFile(),
                        'line' => $v->getLine(),
                        'stack trace' => explode("\n", $v->getTraceAsString())
                    ]
                ]);
            }

            return json_encode($v);
        }, $args);
    }

    private static function _bytesHumanReadable(int $bytes) : string {
        $unit = array('B','kB','mB','gB','tB','pB');
        return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2).' '.$unit[$i];
    }

    public static function handleResponse(THttpResponse $response) {
        if (!self::$enabled) {
            return;
        }
        
        self::memory('memory before response is sent');

        if (!empty(self::$stack) && preg_match('{^text/html}', $response->getHeader('content-type'))) {
            $content = $response->getContent();
            $content .= '<script>';

            foreach (self::$stack as $v) {
                $time = round($v[2] - self::$startTime, 10);
                $file = $v[1][0]['file'];
                $line = $v[1][0]['line'];

                $callerClass = $v[1][1]['class'];
                $callerFn = $v[1][1]['function'];

                $meta = [
                    'file' => $file,
                    'line' => $line,
                    'call' => $callerClass.' :: '.$callerFn
                ];

                $content .= "\n{$v[0]}('[TF]', $time, ".json_encode('['.basename($meta['file']).':'.$meta['line'].']').', '.implode(', ', $v[3]).", '@', ".json_encode($meta).");";
            }

            $content .= "\n</script>";
            $response->setContent($content);
        }
    }
}