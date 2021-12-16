<?php

declare(strict_types=1);

namespace Loner\Console\Command;

use Loner\Console\Console;
use Loner\Console\Input\{InputConcrete, InputDefinition, Input};
use Loner\Console\Output\Output;

/**
 * 命令
 *
 * @package Loner\Console\Command
 */
interface CommandInterface
{
    /**
     * 获取命名空间
     *
     * @return string
     */
    public function getNamespace(): string;

    /**
     * 设置名称
     *
     * @param string $name
     */
    public function setName(string $name): void;

    /**
     * 获取名称
     *
     * @return string
     */
    public function getName(): string;

    /**
     * 设置说明
     *
     * @param string $description
     */
    public function setDescription(string $description): void;

    /**
     * 获取说明
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * 设置控制台实例
     *
     * @param Console $console
     */
    public function setConsole(Console $console): void;

    /**
     * 获取控制台实例
     *
     * @return Console|null
     */
    public function getConsole(): ?Console;

    /**
     * 添加使用实例
     *
     * @param string $usage
     */
    public function addUsage(string $usage): void;

    /**
     * 获取使用实例列表
     *
     * @return string[]
     */
    public function getUsages(): array;

    /**
     * 获取摘要
     *
     * @param bool $short
     * @return string
     */
    public function getSynopsis(bool $short = false): string;

    /**
     * 获取输入定义
     *
     * @return InputDefinition
     */
    public function getDefinition(): InputDefinition;

    /**
     * 获取输入实体
     *
     * @param Input $input
     * @return InputConcrete
     */
    public function getConcrete(Input $input): InputConcrete;

    /**
     * 运行命令
     *
     * @param Input $input
     * @param Output $output
     * @return int
     */
    public function run(Input $input, Output $output): int;
}
