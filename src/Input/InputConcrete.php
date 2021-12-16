<?php

declare(strict_types=1);

namespace Loner\Console\Input;

/**
 * 输入实体
 *
 * @package Loner\Console\Input
 */
class InputConcrete
{
    /**
     * 初始化实体信息
     *
     * @param <string[]|string>[] $arguments 【位置 => 字符串值列表、字符串值】
     * @param <string[]|string|null>[] $options 【名称 => 字符串值列表、字符串值、空】
     */
    public function __construct(private array $arguments = [], private array $options = [])
    {
    }

    /**
     * 获取参数值
     *
     * @param int $position
     * @return string[]|string|null
     */
    public function getArgument(int $position): array|string|null
    {
        return $this->arguments[$position] ?? null;
    }

    /**
     * 判断选项是否存在
     *
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return key_exists($name, $this->options);
    }

    /**
     * 获取选项值
     *
     * @param string $name
     * @return string[]|string|null
     */
    public function getOption(string $name): array|string|null
    {
        return $this->options[$name] ?? null;
    }
}
