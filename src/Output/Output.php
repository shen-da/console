<?php

declare(strict_types=1);

namespace Loner\Console\Output;

use Loner\Console\Exception\InvalidArgumentException;

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
        $this->stream = self::getOutputStream();
        $this->setMode($mode);
    }

    /**
     * 输出消息
     *
     * @param string $message
     */
    public function write(string $message = ''): void
    {
        if ($this->isQuiet()) {
            return;
        }

        switch ($this->mode) {
            case self::OUTPUT_DECORATE:
                $message = $this->formatter->format($message);
                break;
            case self::OUTPUT_RAW:
                break;
            case self::OUTPUT_PLAIN:
                $message = strip_tags($message);
                break;
        }

        @fwrite($this->stream, $message);
        fflush($this->stream);
    }

    /**
     * 输出消息并换行
     *
     * @param string $message
     * @param int $lines
     */
    public function writeln(string $message = '', int $lines = 1): void
    {
        $this->write($message . str_repeat(PHP_EOL, $lines));
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
     * 获取输出流
     *
     * @return resource
     */
    private static function getOutputStream()
    {
        $filename = str_contains(PHP_OS, 'OS400') ? 'php://stdout' : 'php://output';
        return fopen($filename, 'w');
    }
}
