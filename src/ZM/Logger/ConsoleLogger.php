<?php

declare(strict_types=1);

namespace ZM\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

class ConsoleLogger extends AbstractLogger
{
    public const VERSION = '1.0.0-alpha';

    /**
     * 日志输出格式
     *
     * @var string
     */
    public static $format = '[%date%] [%level%] %process%%body%';

    /**
     * 日志输出日期格式
     *
     * @var string
     */
    public static $date_format = 'Y-m-d H:i:s';

    /**
     * 颜色表
     *
     * @var array{int, array{string}}
     */
    protected static $styles = [
        ['blink', 'white', 'bg_bright_red'], // emergency
        ['white', 'bg_bright_red'], // alert
        ['underline', 'red'], // critical
        ['red'], // error
        ['bright_yellow'], // warning
        ['cyan'], // notice
        ['green'], // info
        ['gray'], // debug
    ];

    /**
     * 等级表
     *
     * @var array{int, int}
     */
    protected static $levels = [
        LogLevel::EMERGENCY, // 0
        LogLevel::ALERT, // 1
        LogLevel::CRITICAL, // 2
        LogLevel::ERROR, // 3
        LogLevel::WARNING, // 4
        LogLevel::NOTICE, // 5
        LogLevel::INFO, // 6
        LogLevel::DEBUG, // 7
    ];

    /**
     * 当前日志等级
     *
     * @var int
     */
    protected static $log_level;

    /**
     * 创建一个 ConsoleLogger 实例
     *
     * @param string $level 日志等级
     */
    public function __construct(string $level = LogLevel::INFO)
    {
        self::$log_level = $this->castLogLevel($level);
    }

    /**
     * 获取当前样式表
     *
     * @return array{int, array{string}}
     */
    public static function getStyles(): array
    {
        return self::$styles;
    }

    /**
     * 获取版本号
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * 打印执行栈
     */
    public function trace(): void
    {
        $log = "Stack trace:\n";
        $trace = debug_backtrace();
        //array_shift($trace);
        foreach ($trace as $i => $t) {
            if (!isset($t['file'])) {
                $t['file'] = 'unknown';
            }
            if (!isset($t['line'])) {
                $t['line'] = 0;
            }
            $log .= "#{$i} {$t['file']}({$t['line']}): ";
            if (isset($t['object']) && is_object($t['object'])) {
                $log .= get_class($t['object']) . '->';
            }
            $log .= "{$t['function']}()\n";
        }
        $log = $this->colorize($log, $this->castLogLevel(LogLevel::DEBUG));
        echo $log;
    }

    /**
     * 根据日志等级将样式应用至指定字符串
     *
     * @param mixed $string 日志内容
     * @param int   $level  日志等级
     */
    public function colorize($string, int $level): string
    {
        $string = $this->stringify($string);
        $styles = self::$styles[$level] ?? [];
        return ConsoleColor::apply($styles, $string)->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $level = $this->castLogLevel($level);

        if (!$this->shouldLog($level)) {
            return;
        }

        $output = str_replace(
            ['%date%', '%level%', '%body%'],
            [date(self::$date_format), strtoupper(substr(self::$levels[$level], 0, 4)), $message],
            self::$format
        );
        $output = $this->interpolate($output, $context);
        echo $this->colorize($output, $level) . "\n";
    }

    /**
     * 转换日志等级
     */
    private function castLogLevel(string $level): int
    {
        if (in_array($level, self::$levels, true)) {
            return array_flip(self::$levels)[$level];
        }

        throw new InvalidArgumentException('无效的日志等级');
    }

    /**
     * 将日志内容转换为字符串
     *
     * @param mixed $item 日志内容
     */
    private function stringify($item): string
    {
        switch (true) {
            case is_callable($item):
                if (is_array($item)) {
                    if (is_object($item[0])) {
                        return get_class($item[0]) . '@' . $item[1];
                    }
                    return $item[0] . '::' . $item[1];
                }
                return 'closure';
            case is_string($item):
                return $item;
            case is_array($item):
                return 'array' . json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS);
            case is_object($item):
                return get_class($item);
            case is_resource($item):
                return 'resource(' . get_resource_type($item) . ')';
            case is_null($item):
                return 'null';
            case is_bool($item):
                return $item ? 'true' : 'false';
            case is_float($item):
            case is_int($item):
                return (string) $item;
            default:
                return 'unknown';
        }
    }

    /**
     * 判断是否应该记录该等级日志
     */
    private function shouldLog(int $level): bool
    {
        return $level <= self::$log_level;
    }

    /**
     * 插入变量到日志内容中
     *
     * @param string $message 日志内容
     * @param array  $context 变量列表
     */
    private function interpolate(string $message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = $this->stringify($value);
        }

        return strtr($message, $replace);
    }
}
