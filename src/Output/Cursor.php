<?php

declare(strict_types=1);

namespace Loner\Console\Output;

/**
 * 光标
 *
 * @package Loner\Console\Output
 */
class Cursor
{
    /**
     * 输入流
     *
     * @var resource|null
     */
    private $input;

    /**
     * 初始化光标输入流与输出
     *
     * @param Output $output
     * @param resource|null $inputStream
     */
    public function __construct(private Output $output, $inputStream = null)
    {
        $this->input = $inputStream ?? STDIN;
    }

    /**
     * 上移
     *
     * @param int $rows
     * @return $this
     */
    public function up(int $rows = 1): self
    {
        $this->output->write(sprintf("\x1b[%dA", $rows));
        return $this;
    }

    /**
     * 下移
     *
     * @param int $rows
     * @return $this
     */
    public function down(int $rows = 1): self
    {
        $this->output->write(sprintf("\x1b[%dB", $rows));
        return $this;
    }

    /**
     * 右移
     *
     * @param int $columns
     * @return $this
     */
    public function right(int $columns = 1): self
    {
        $this->output->write(sprintf("\x1b[%dC", $columns));

        return $this;
    }

    /**
     * 左移
     *
     * @param int $columns
     * @return $this
     */
    public function left(int $columns = 1): self
    {
        $this->output->write(sprintf("\x1b[%dD", $columns));
        return $this;
    }

    /**
     * 移动到指定列
     *
     * @param int $column
     * @return $this
     */
    public function toColumn(int $column): self
    {
        $this->output->write(sprintf("\x1b[%dG", $column));
        return $this;
    }

    /**
     * 移动到指定位置
     *
     * @param int $row
     * @param int $column
     * @return $this
     */
    public function toPosition(int $row, int $column): self
    {
        $this->output->write(sprintf("\x1b[%d;%dH", $row + 1, $column));
        return $this;
    }

    /**
     * 保存光标位置
     *
     * @return $this
     */
    public function savePosition(): self
    {
        # \x1b7
        $this->output->write("\x1b[s");
        return $this;
    }

    /**
     * 恢复光标位置
     *
     * @return $this
     */
    public function restorePosition(): self
    {
        # \x1b8
        $this->output->write("\x1b[u");
        return $this;
    }

    /**
     * 隐藏
     *
     * @return $this
     */
    public function hide(): self
    {
        $this->output->write("\x1b[?25l");
        return $this;
    }

    /**
     * 显示
     *
     * @return $this
     */
    public function show(): self
    {
        $this->output->write("\x1b[?25h\x1b[?0c");
        return $this;
    }

    /**
     * 清除当前行
     *
     * @return $this
     */
    public function clearLine(): self
    {
        $this->output->write("\x1b[2K");

        return $this;
    }

    /**
     * 清除当前行光标之后的内容
     *
     * @return $this
     */
    public function clearLineAfter(): self
    {
        $this->output->write("\x1b[K");
        return $this;
    }

    /**
     * 清屏
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->output->write("\x1b[2J");
        return $this;
    }

    /**
     * 清除光标到屏幕尾部内容
     *
     * @return $this
     */
    public function clearAfter(): self
    {
        $this->output->write("\x1b[0J");
        return $this;
    }

    /**
     * 返回光标当前坐标
     *
     * @return int[]
     */
    public function getCurrentPosition(): array
    {
        static $isTtySupported;

        if (null === $isTtySupported && function_exists('proc_open')) {
            $isTtySupported = (bool)@proc_open(
                'echo 1 >/dev/null',
                [
                    ['file', '/dev/tty', 'r'],
                    ['file', '/dev/tty', 'w'],
                    ['file', '/dev/tty', 'w']
                ],
                $pipes
            );
        }

        if (!$isTtySupported) {
            return [1, 1];
        }

        $sttyMode = shell_exec('stty -g');
        shell_exec('stty -icanon -echo');

        @fwrite($this->input, "\033[6n");

        $code = trim(fread($this->input, 1024));

        shell_exec(sprintf('stty %s', $sttyMode));

        sscanf($code, "\033[%d;%dR", $row, $column);

        return [$column, $row];
    }
}
