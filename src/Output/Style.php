<?php

declare(strict_types=1);

namespace Loner\Console\Output;

use Loner\Console\Exception\InvalidArgumentException;

/**
 * 输出样式
 *
 * @package Loner\Console\Output
 */
class Style
{
    /**
     * 颜色：黑
     */
    public const BLACK = 'black';

    /**
     * 颜色：红
     */
    public const RED = 'red';

    /**
     * 颜色：绿
     */
    public const GREEN = 'green';

    /**
     * 颜色：黄
     */
    public const YELLOW = 'yellow';

    /**
     * 颜色：蓝
     */
    public const BLUE = 'blue';

    /**
     * 颜色：品红
     */
    public const MAGENTA = 'magenta';

    /**
     * 颜色：青
     */
    public const CYAN = 'cyan';

    /**
     * 颜色：白
     */
    public const WHITE = 'white';

    /**
     * 配置：高亮
     */
    public const HIGHLIGHT = 'highlight';

    /**
     * 配置：下边线
     */
    public const UNDERLINE = 'underline';

    /**
     * 配置：闪烁
     */
    public const BLINK = 'blink';

    /**
     * 配置：反显（前后景倒置）
     */
    public const REVERSE = 'reverse';

    /**
     * 配置：消隐（前景空白）
     */
    public const CONCEAL = 'conceal';

    /**
     * 支持颜色
     */
    private const COLORS = [
        self::BLACK => '0',
        self::RED => '1',
        self::GREEN => '2',
        self::YELLOW => '3',
        self::BLUE => '4',
        self::MAGENTA => '5',
        self::CYAN => '6',
        self::WHITE => '7'
    ];

    /**
     * 支持配置
     */
    private const OPTIONS = [
        self::HIGHLIGHT => '1',
        self::UNDERLINE => '4',
        self::BLINK => '5',
        self::REVERSE => '7',
        self::CONCEAL => '8'
    ];

    /**
     * 是否支持真彩色
     *
     * @var bool|null
     */
    private static ?bool $trueColor;

    /**
     * 是否支持超链接
     *
     * @var bool|null
     */
    private static ?bool $hrefGracefully;

    /**
     * 前景色
     *
     * @var string|null
     */
    private ?string $fgColor = null;

    /**
     * 背景色
     *
     * @var string|null
     */
    private ?string $bgColor = null;

    /**
     * 超连接
     *
     * @var string|null
     */
    private ?string $href = null;

    /**
     * 配置列表
     *
     * @var true[]
     */
    private array $options = [];

    /**
     * 初始化样式
     *
     * @param string|null $fgColor
     * @param string|null $bgColor
     * @param string ...$options
     */
    public function __construct(string $fgColor = null, string $bgColor = null, string ...$options)
    {
        if ($fgColor !== null) {
            $this->setFgColor($fgColor);
        }

        if ($bgColor !== null) {
            $this->setBgColor($bgColor);
        }

        $this->setOptions(...$options);
    }

    /**
     * 设置前景色
     *
     * @param string $color
     */
    public function setFgColor(string $color): void
    {
        $this->fgColor = self::getColorCode($color);
    }

    /**
     * 设置背景色
     *
     * @param string $color
     */
    public function setBgColor(string $color): void
    {
        $this->bgColor = self::getColorCode($color);
    }

    /**
     * 批量设置选项
     *
     * @param string ...$options
     */
    public function setOptions(string ...$options): void
    {
        array_walk($options, [$this, 'setOption']);
    }

    /**
     * 设置选项
     *
     * @param string $option
     */
    public function setOption(string $option): void
    {
        self::checkOption($option);
        $this->options[$option] = self::OPTIONS[$option];
    }

    /**
     * 批量移除选项
     *
     * @param string ...$options
     */
    public function unsetOptions(string ...$options): void
    {
        array_walk($options, [$this, 'unsetOption']);
    }

    /**
     * 移除选项
     *
     * @param string $option
     */
    public function unsetOption(string $option): void
    {
        self::checkOption($option);
        unset($this->options[$option]);
    }

    /**
     * 设置超链接
     *
     * @param string $href
     */
    public function setHref(string $href): void
    {
        $this->href = $href;
    }

    /**
     * 返回文本样式应用内容
     *
     * @param string $text
     * @return string
     */
    public function apply(string $text): string
    {
        if (self::hefGracefully() && $this->href) {
            $text = "\033]8;;$this->href\033\\$text\033]8;;\033\\";
        }

        return $this->set() . $text . $this->unset();
    }

    /**
     * 返回设置内容
     *
     * @return string
     */
    public function set(): string
    {
        $codes = [];

        if (null !== $this->fgColor) {
            $codes[] = '3' . $this->fgColor;
        }

        if (null !== $this->bgColor) {
            $codes[] = '4' . $this->bgColor;
        }

        if (!empty($this->options)) {
            array_push($codes, ...array_values($this->options));
        }

        return empty($codes) ? '' : sprintf("\033[%sm", implode(';', $codes));
    }

    /**
     * 返回复位内容
     *
     * @return string
     */
    public function unset(): string
    {
        return $this->fgColor === null && $this->bgColor === null && empty($this->options) ? '' : "\033[0m";
    }

    /**
     * 检测颜色并返回
     *
     * @param string $color
     * @return string
     */
    private static function getColorCode(string $color): string
    {
        if (null === $code = self::getColor($color)) {
            throw new InvalidArgumentException(sprintf('The color "%s" is invalid.', $color));
        }

        return $code;
    }

    /**
     * 检测配置
     *
     * @param string $option
     */
    private static function checkOption(string $option): void
    {
        if (!isset(self::OPTIONS[$option])) {
            throw new InvalidArgumentException(sprintf('Invalid option "%s".', $option));
        }
    }

    /**
     * 获取颜色码
     *
     * @param string $color
     * @return string|null
     */
    private static function getColor(string $color): ?string
    {
        return str_starts_with($color, '#') ? self::getColorByHex(substr($color, 1)) : self::getCommonColor($color);
    }

    /**
     * 获取常规颜色码
     *
     * @param string $color
     * @return string|null
     */
    private static function getCommonColor(string $color): ?string
    {
        return self::COLORS[$color] ?? null;
    }

    /**
     * 通过十六进制串获取颜色码
     *
     * @param string $color
     * @return string|null
     */
    private static function getColorByHex(string $color): ?string
    {
        switch (strlen($color)) {
            case 3:
                if (preg_match('/^[\da-fA-F]{3}$/', $color)) {
                    return self::getColorByRGB(...array_map(fn($color) => hexdec($color . $color), str_split($color)));
                }
                break;
            case 6:
                if (preg_match('/^[\da-fA-F]{6}$/', $color)) {
                    return self::getColorByRGB(...array_map('hexdec', str_split($color, 2)));
                }
                break;
        }
        return null;
    }

    /**
     * 通过三基色获取颜色码
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return string
     */
    private static function getColorByRGB(int $red, int $green, int $blue): string
    {
        return self::trueColor()
            ? sprintf('8;2;%d;%d;%d', $red, $green, $blue)
            : round($red / 255) . round($green / 255) . round($blue / 255);
    }

    /**
     * 是否支持真彩色
     *
     * @return bool
     */
    private static function trueColor(): bool
    {
        return self::$trueColor ??= getenv('COLORTERM') === 'truecolor';
    }

    /**
     * 是否支持超链接
     *
     * @return bool
     */
    private static function hefGracefully(): bool
    {
        return self::$hrefGracefully ??= 'JetBrains-JediTerm' !== getenv('TERMINAL_EMULATOR')
            && (!getenv('KONSOLE_VERSION') || (int)getenv('KONSOLE_VERSION') > 201100);
    }
}
