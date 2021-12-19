<?php

declare(strict_types=1);

namespace Loner\Console\Output;

use Loner\Console\Exception\InvalidArgumentException;
use Throwable;

/**
 * 输出
 *
 * @package Loner\Console\Output
 */
class Output
{
    /**
     * 消息输出模式：装饰
     */
    public const OUTPUT_DECORATE = 1;

    /**
     * 消息输出模式：文本
     */
    public const OUTPUT_PLAIN = 2;

    /**
     * 消息输出模式：原始
     */
    public const OUTPUT_RAW = 4;

    /**
     * 消息输出模式：静默
     */
    public const OUTPUT_QUIET = 8;

    /**
     * 是否运行在 IBM iSeries（OS400）
     *
     * @var false
     */
    private static bool $isRunningOS400;

    /**
     * 消息输出模式
     *
     * @var int
     */
    private int $mode;

    /**
     * 输出流
     *
     * @var resource
     */
    private $stream;

    /**
     * 初始化输出信息
     *
     * @param Formatter $formatter
     * @param int $mode
     */
    public function __construct(private Formatter $formatter, int $mode = self::OUTPUT_DECORATE)
    {
        $this->stream = self::openOutputStream();
        $this->setMode($mode);
    }

    /**
     * 输出消息
     *
     * @param string|iterable $messages
     * @param bool $newlines
     * @param int|null $mode
     */
    public function write(string|iterable $messages, bool $newlines = false, int $mode = null): void
    {
        if ($this->isQuiet()) {
            return;
        }

        if ($mode === null) {
            $mode = $this->mode;
        }

        if (is_iterable($messages)) {
            foreach ($messages as $message) {
                $this->doWrite($message, $mode, $newlines);
            }
        } else {
            $this->doWrite($messages, $mode, $newlines);
        }


        fflush($this->stream);
    }

    /**
     * 输出消息并换行
     *
     * @param string|iterable $messages
     * @param int|null $mode
     */
    public function writeln(string|iterable $messages = '', int $mode = null): void
    {
        $this->write($messages, true, $mode);
    }

    /**
     * 输出异常信息
     *
     * @param Throwable $throwable
     * @param bool $newlines
     * @param int|null $mode
     */
    public function throwable(Throwable $throwable, bool $newlines = true, int $mode = null): void
    {
        $messages = sprintf('%s<error>%s</error>', PHP_EOL, $throwable->getMessage());
        $this->getErrorOutput()->write($messages, $newlines, $mode);
    }

    /**
     * 获取消息输出模式
     *
     * @param int $mode
     */
    public function setMode(int $mode): void
    {
        if (!in_array($mode, [self::OUTPUT_DECORATE, self::OUTPUT_PLAIN, self::OUTPUT_RAW, self::OUTPUT_QUIET])) {
            throw new InvalidArgumentException(sprintf('The output mode "%s" is not valid.', $mode));
        }

        $this->mode = $mode;
        $this->formatter->setDecorated($mode === self::OUTPUT_DECORATE);
    }

    /**
     * 获取消息输出模式
     *
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * 判断是否装饰模式
     *
     * @return bool
     */
    public function isDecorated(): bool
    {
        return $this->formatter->isDecorated();
    }

    /**
     * 判断是否文本模式
     *
     * @return bool
     */
    public function isPlain(): bool
    {
        return $this->mode === self::OUTPUT_PLAIN;
    }

    /**
     * 判断是否原始模式
     *
     * @return bool
     */
    public function isRaw(): bool
    {
        return $this->mode === self::OUTPUT_RAW;
    }

    /**
     * 判断是否静默模式
     *
     * @return bool
     */
    public function isQuiet(): bool
    {
        return $this->mode === self::OUTPUT_QUIET;
    }

    /**
     * 设置格式器
     *
     * @param Formatter $formatter
     */
    public function setFormatter(Formatter $formatter): void
    {
        $this->formatter = $formatter;
    }

    /**
     * 获取格式器
     *
     * @return Formatter
     */
    public function getFormatter(): Formatter
    {
        return $this->formatter;
    }

    /**
     * 获取错误输出
     *
     * @return $this
     */
    public function getErrorOutput(): self
    {
        return $this->errorOutput ??= self::createErrorOutput($this);
    }

    /**
     * 创建错误输出
     *
     * @param Output $output
     * @return static
     */
    private static function createErrorOutput(self $output): self
    {
        $new = clone $output;
        $new->stream = self::openErrorStream();
        return $new;
    }

    /**
     * 获取输出流
     *
     * @return resource
     */
    private static function openOutputStream()
    {
        $filename = self::isRunningOS400() ? 'php://output' : 'php://stdout';
        return fopen($filename, 'w');
    }

    /**
     * 获取错误流
     *
     * @return resource
     */
    private static function openErrorStream()
    {
        $filename = self::isRunningOS400() ? 'php://output' : 'php://stderr';
        return fopen($filename, 'w');
    }

    /**
     * 检查运行是否为 IBM iSeries（OS400）
     *  该环境不能正确地将 ASCII 字符编码转换为 EBCDIC
     *
     * @return bool
     */
    private static function isRunningOS400(): bool
    {
        if (!isset(self::$isRunningOS400)) {
            self::$isRunningOS400 = false;
            foreach ([php_uname('s'), getenv('OSTYPE'), PHP_OS] as $check) {
                if ($check && stripos($check, 'OS400') !== false) {
                    self::$isRunningOS400 = true;
                    break;
                }
            }
        }

        return self::$isRunningOS400;
    }

    /**
     * 输出消息
     *
     * @param string $message
     * @param int $mode
     * @param bool $newlines
     */
    private function doWrite(string $message, int $mode, bool $newlines): void
    {
        switch ($mode) {
            case self::OUTPUT_DECORATE:
                $message = $this->formatter->format($message);
                break;
            case self::OUTPUT_RAW:
                break;
            case self::OUTPUT_PLAIN:
                $message = strip_tags($message);
                break;
        }

        if ($newlines) {
            $message .= PHP_EOL;
        }

        @fwrite($this->stream, $message);
    }
}
