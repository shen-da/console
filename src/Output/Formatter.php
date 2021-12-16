<?php

declare(strict_types=1);

namespace Loner\Console\Output;

/**
 * 输出格式器
 *
 * @package Loner\Console\Output
 */
class Formatter
{
    /**
     * 是否装饰
     *
     * @var bool
     */
    private bool $decorated = false;

    /**
     * 默认样式配置
     *
     * @var string[][]
     */
    private array $defaultStylesOptions = [
        'css' => [],
        'error' => [Style::WHITE, Style::RED],
        'info' => [Style::GREEN],
        'comment' => [Style::YELLOW]
    ];

    /**
     * 样式库
     *
     * @var Style[]
     */
    private array $styles = [];

    /**
     * 初始化格式器
     *
     * @param bool $decorated 是否装饰
     * @param Style[] $styles 添加样式库
     */
    public function __construct(bool $decorated = false, array $styles = [])
    {
        foreach ($styles as $name => $style) {
            $this->setStyle($name, $style);
        }

        $this->setDecorated($decorated);
    }

    /**
     * 设置装饰是否启用
     *
     * @param bool $decorated
     */
    public function setDecorated(bool $decorated): void
    {
        if ($decorated === $this->decorated) {
            return;
        }

        $this->decorated = $decorated;

        if ($decorated) {
            $this->setDefaultStyles();;
        }
    }

    /**
     * 返回装饰是否启用
     *
     * @return bool
     */
    public function isDecorated(): bool
    {
        return $this->decorated;
    }

    /**
     * 设置样式
     *
     * @param string $name
     * @param Style $style
     */
    public function setStyle(string $name, Style $style): void
    {
        $this->styles[$name] = $style;
    }

    /**
     * 判断样式是否存在
     *
     * @param string $name
     * @return bool
     */
    public function hasStyle(string $name): bool
    {
        return isset($this->styles[$name]);
    }

    /**
     * 获取样式
     *
     * @param string $name
     * @return Style|null
     */
    public function getStyle(string $name): ?Style
    {
        return $this->styles[$name] ?? null;
    }

    /**
     * 格式化消息
     *
     * @param string $message
     * @return string
     */
    public function format(string $message = ''): string
    {
        if (!$this->isDecorated()) {
            return $message;
        }

        return (string)preg_replace_callback('/<([a-z]\w*)(.*?)>(.*?)<\/\1>/s', function ($match) {

            $subMessage = $this->format($match[3]);

            if (!$this->hasStyle($match[1])) {
                return "<{$match[1]}{$match[2]}>{$subMessage}</{$match[1]}>";
            }

            $style = $this->getStyle($match[1]);

            if (preg_match_all('/\b(fg|bg|href|options)=([\'"]?)([^\2\s]+?)\2(?=\s|$)/s', $match[2], $_matches, PREG_SET_ORDER)) {
                foreach ($_matches as $_match) {
                    $_match3 = str_replace(' ', '', $_match[3]);
                    switch ($_match[1]) {
                        case 'fg':
                            $style->setFgColor($_match3);
                            break;
                        case 'bg':
                            $style->setBgColor($_match3);
                            break;
                        case 'href':
                            $style->setHref($_match3);
                            break;
                        case 'options':
                            $style->setOptions(...explode(',', $_match3));
                            break;
                    }
                }
            }

            return $style->apply($subMessage);

        }, $message);
    }

    /**
     * 设置默认样式
     */
    private function setDefaultStyles(): void
    {
        foreach ($this->defaultStylesOptions as $name => $options) {
            if (!$this->hasStyle($name)) {
                $this->setStyle($name, new Style(...$options));
            }
        }
    }
}
