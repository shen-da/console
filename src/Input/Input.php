<?php

declare(strict_types=1);

namespace Loner\Console\Input;

use Loner\Console\Exception\RuntimeException;
use Loner\Console\Input\Definition\Option;

/**
 * 输入
 *
 * @package Loner\Console\Input
 */
class Input
{
    /**
     * 输入流
     *
     * @var resource|null
     */
    private $stream;

    /**
     * 指令解析数组
     *
     * @var string[]
     */
    private array $parsing;

    /**
     * 位置参数组
     *
     * @var string[]
     */
    private array $arguments = [];

    /**
     * 长选项数组
     *
     * @var string[][] [名称 => 值列表]
     */
    private array $options = [];

    /**
     * 短选项数组
     *
     * @var string[][] [名称 => 值列表]
     */
    private array $shortOptions = [];

    /**
     * 获取默认输入指令数组
     *
     * @return string[]
     */
    private static function getDefaultTokens(): array
    {
        return isset($_SERVER['argv']) ? array_slice($_SERVER['argv'], 1) : [];
    }

    /**
     * 解析输入指令
     *
     * @param string[]|null $tokens
     */
    public function __construct(array $tokens = null)
    {
        $this->parseTokens($tokens ?? self::getDefaultTokens());
    }

    /**
     * 获取输入流
     *
     * @return resource|null
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * 设置输入流
     *
     * @param resource $stream
     * @return $this
     */
    public function setStream($stream): self
    {
        $this->stream = $stream;
        return $this;
    }

    /**
     * 返回常规参数
     *
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * 返回常规选项
     *
     * @return string[][]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * 返回快捷名选项
     *
     * @return string[][]
     */
    public function getShortOptions(): array
    {
        return $this->shortOptions;
    }

    /**
     * 判断是否含指定位置参数
     *
     * @param int $position
     * @return bool
     */
    public function hasArgument(int $position): bool
    {
        return isset($this->arguments[$position]);
    }

    /**
     * 获取位置参数值
     *
     * @param int $position
     * @return string|null
     */
    public function getArgumentValue(int $position): ?string
    {
        return $this->arguments[$position] ?? null;
    }

    /**
     * 获取复合位置参数值列表
     *
     * @param int $position
     * @return array|null
     */
    public function getComplexArgumentValues(int $position): ?array
    {
        return array_slice($this->arguments, $position) ?: null;
    }

    /**
     * 判断是否含有指定选项
     *
     * @param Option $option
     * @return bool
     */
    public function hasOption(Option $option): bool
    {
        if (isset($this->options[$option->getName()])) {
            return true;
        }

        if (null === $shortcut = $option->getShortcut()) {
            return false;
        }

        return isset($this->shortOptions[$shortcut]);
    }

    /**
     * 获取选项值
     *
     * @param Option $option
     * @return string[]|null
     */
    public function getOptionValues(Option $option): ?array
    {
        $name = $option->getName();

        $hasLongOption = isset($this->options[$name]);

        $shortcut = $option->getShortcut();
        $hasShortOption = $shortcut !== null && isset($this->shortOptions[$shortcut]);

        return $hasLongOption
            ? $hasShortOption
                ? array_merge($this->options[$name], $this->shortOptions[$shortcut])
                : $this->options[$name]
            : $this->shortOptions[$shortcut] ?? null;
    }

    /**
     * 返回新对象：从前往后，丢弃指定数量的位置参数；丢弃给定选项参数
     *
     * @param int $start
     * @param Option ...$dropOptions
     * @return Input
     */
    public function clone(int $start = 0, Option ...$dropOptions): Input
    {
        $new = clone $this;
        $new->arguments = array_slice($this->arguments, $start);
        foreach ($dropOptions as $option) {
            unset($new->options[$option->getName()]);
            if (null !== $shortcut = $option->getShortcut()) {
                unset($new->shortOptions[$shortcut]);
            }
        }
        return $new;
    }

    /**
     * 解析参数
     *
     * @param array $tokens
     * @throws RuntimeException
     */
    private function parseTokens(array $tokens): void
    {
        $this->parsing = $tokens;
        while (null !== $token = array_shift($this->parsing)) {
            str_starts_with($token, '-') ? $this->parseNotArgument($token) : $this->fillArgument($token);
        }
    }

    /**
     * 解析非位置参数部分
     *
     * @param string $token
     * @throws RuntimeException
     */
    private function parseNotArgument(string $token): void
    {
        str_starts_with($token, '--')
            ? $token === '--' ? $this->fillArguments() : $this->parseLongOption(substr($token, 2))
            : $this->parseShortOption(substr($token, 1));
    }

    /**
     * 解析长选项
     *
     * @param string $name
     * @throws RuntimeException
     */
    private function parseLongOption(string $name): void
    {
        if (str_contains($name, '=')) {
            [$name, $value] = explode('=', $name, 2);

            if (!preg_match('/^[^\s=]+$/', $name)) {
                throw new RuntimeException(sprintf('Invalid option assignment statement: "%s". Option name must be a non empty string without "=" and spaces.', $name));
            }

            if (isset($this->options[$name])) {
                $this->options[$name][] = $value;
            } else {
                $this->options[$name] = [$value];
            }
        }

        $this->parseOption($name, $this->options);
    }

    /**
     * 解析短选项
     *
     * @param string $name
     * @throws RuntimeException
     */
    private function parseShortOption(string $name): void
    {
        if ($name === '') {
            throw new RuntimeException('The option shortcut cannot be empty.');
        }

        if (str_contains($name, '-')) {
            throw new RuntimeException(sprintf('Invalid concatenation shortcut name for option: "%s". Option shortcut name cannot be "-".', $name));
        }

        $end = strlen($name) - 1;

        if ($end > 0) {
            for ($i = $end - 1; $i >= 0; $i--) {
                $shortcut = $name[$i];
                if (!isset($this->shortOptions[$shortcut])) {
                    $this->shortOptions[$shortcut] = [];
                }
            }

            $name = $name[$end];
        }

        $this->parseOption($name, $this->shortOptions);
    }

    /**
     * 解析选项
     *
     * @param string $name
     * @param array $options
     * @throws RuntimeException
     */
    private function parseOption(string $name, array &$options): void
    {
        if (!isset($options[$name])) {
            $options[$name] = [];
        }

        while (null !== $token = array_shift($this->parsing)) {
            if (str_starts_with($token, '-')) {
                $this->parseNotArgument($token);
            } else {
                $options[$name][] = $token;
            }
        }
    }

    /**
     * 批量填充位置参数
     */
    private function fillArguments(): void
    {
        while (null !== $token = array_shift($this->parsing)) {
            $this->fillArgument($token);
        }
    }

    /**
     * 填充位置参数
     *
     * @param string $value
     */
    private function fillArgument(string $value): void
    {
        $this->arguments[] = $value;
    }
}
