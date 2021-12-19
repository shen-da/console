<?php

declare(strict_types=1);

namespace Loner\Console;

/**
 * 终端
 *
 * @package Loner\Console
 */
class Terminal
{
    /**
     * 终端宽度
     *
     * @var int
     */
    private static int $width;

    /**
     * 终端高度
     *
     * @var int
     */
    private static int $height;

    /**
     * stty 是否可用
     *
     * @var bool
     */
    private static bool $stty;

    /**
     * 获取终端宽度
     *
     * @return int
     */
    public function getWidth(): int
    {
        $width = getenv('COLUMNS');
        if (false !== $width) {
            return (int)trim($width);
        }

        if (null === self::$width) {
            self::initDimensions();
        }

        return self::$width ?: 80;
    }

    /**
     * 获取终端高度
     *
     * @return int
     */
    public function getHeight(): int
    {
        $height = getenv('LINES');
        if (false !== $height) {
            return (int)trim($height);
        }

        if (null === self::$height) {
            self::initDimensions();
        }

        return self::$height ?: 50;
    }

    /**
     * 返回 stty 是否可用
     *
     * @return bool
     */
    public static function hasSttyAvailable(): bool
    {
        if (isset(self::$stty)) {
            return self::$stty;
        }

        if (!function_exists('exec')) {
            return false;
        }

        exec('stty 2>&1', $output, $exitCode);

        return self::$stty = 0 === $exitCode;
    }

    /**
     * 初始化终端宽高
     */
    private static function initDimensions(): void
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            if (preg_match('/^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$/', trim(getenv('ANSICON')), $matches)) {
                // extract [w, H] from "wxh (WxH)"
                // or [w, h] from "wxh"
                self::$width = (int)$matches[1];
                self::$height = isset($matches[4]) ? (int)$matches[4] : (int)$matches[2];
            } elseif (!self::hasVt100Support() && self::hasSttyAvailable()) {
                // only use stty on Windows if the terminal does not support vt100 (e.g. Windows 7 + git-bash) testing for stty in a Windows 10 vt100-enabled console will implicitly disable vt100 support on STDOUT
                self::initDimensionsUsingStty();
            } elseif (null !== $dimensions = self::getConsoleMode()) {
                // extract [w, h] from "wxh"
                self::$width = (int)$dimensions[0];
                self::$height = (int)$dimensions[1];
            }
        } else {
            self::initDimensionsUsingStty();
        }
    }

    /**
     * 返回 STDOUT 是否支持 vt100（某些 Windows 10+ 配置）
     */
    private static function hasVt100Support(): bool
    {
        return function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(fopen('php://stdout', 'w'));
    }

    /**
     * 从命令“stty -a"执行结果的 columns 信息中解析终端宽高
     */
    private static function initDimensionsUsingStty(): void
    {
        if ($sttyString = self::getSttyColumns()) {
            if (preg_match('/rows.(\d+);.columns.(\d+);/i', $sttyString, $matches)) {
                // extract [w, h] from "rows h; columns w;"
                self::$width = (int)$matches[2];
                self::$height = (int)$matches[1];
            } elseif (preg_match('/;.(\d+).rows;.(\d+).columns/i', $sttyString, $matches)) {
                // extract [w, h] from "; h rows; w columns"
                self::$width = (int)$matches[2];
                self::$height = (int)$matches[1];
            }
        }
    }

    /**
     * 尝试无错执行“mode CON”命令，解析出终端宽高信息并返回
     *
     * @return int[]|null
     */
    private static function getConsoleMode(): ?array
    {
        $info = self::readFromProcess('mode CON');

        if (null === $info || !preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
            return null;
        }

        return [(int)$matches[2], (int)$matches[1]];
    }

    /**
     * 尝试无错执行“stty -a”命令，返回 columns 相关信息
     *
     * @return string|null
     */
    private static function getSttyColumns(): ?string
    {
        return self::readFromProcess('stty -a | grep columns');
    }

    /**
     * 执行指定命令，屏蔽错误，返回输出信息
     *
     * @param string $command
     * @return string|null
     */
    private static function readFromProcess(string $command): ?string
    {
        if (!function_exists('proc_open')) {
            return null;
        }

        // 描述符规范
        //  key: 描述符，常用0（stdin）、1（stdout）、2（stderr）
        //  value：resource/['file', 文件名]/['pipe', 'r'或'w']
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // 执行一个命令，并且打开用来输入/输出的文件指针；返回进程的资源类型
        $process = proc_open($command, $descriptorSpec, $pipes, null, null, ['suppress_errors' => true]);
        if (!is_resource($process)) {
            return null;
        }

        $info = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $info;
    }
}
