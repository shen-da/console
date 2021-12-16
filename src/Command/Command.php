<?php

declare(strict_types=1);

namespace Loner\Console\Command;

use Loner\Console\Console;
use Loner\Console\Input\{Definition\Argument, Definition\Option, InputConcrete, InputDefinition, Input};

/**
 * 基础命令
 *
 * @package Loner\Console\Command
 */
abstract class Command implements CommandInterface
{
    /**
     * 名称
     *
     * @var string
     */
    private string $name;

    /**
     * 说明
     *
     * @var string
     */
    private string $description;

    /**
     * 输入定义
     *
     * @var InputDefinition|null
     */
    private ?InputDefinition $definition;

    /**
     * 控制台实例
     *
     * @var Console|null
     */
    private ?Console $console;

    /**
     * 示例列表
     *
     * @var string[]
     */
    private array $usages = [];

    /**
     * 摘要（简要 + 完整）
     *
     * @var array
     */
    private array $synopsis = [];

    /**
     * 获取默认名称
     *
     * @return string
     */
    abstract public static function getDefaultName(): string;

    /**
     * 获取默认描述
     *
     * @return string
     */
    abstract public static function getDefaultDescription(): string;

    /**
     * 获取输入参数
     *
     * @return <Argument|Option>[]
     */
    abstract public function getDefinitions(): array;

    /**
     * @inheritDoc
     */
    public function getNamespace(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name ??= static::getDefaultName();
    }

    /**
     * @inheritDoc
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return $this->description ??= static::getDefaultDescription();
    }

    /**
     * @inheritDoc
     */
    public function setConsole(Console $console): void
    {
        $this->console = $console;
    }

    /**
     * @inheritDoc
     */
    public function getConsole(): ?Console
    {
        return $this->console;
    }

    /**
     * @inheritDoc
     */
    public function addUsage(string $usage): void
    {
        if (!str_starts_with($usage, $this->getName())) {
            $usage = sprintf('%s %s', $this->getName(), $usage);
        }

        $this->usages[] = $usage;
    }

    /**
     * @inheritDoc
     */
    public function getUsages(): array
    {
        return $this->usages;
    }

    /**
     * @inheritDoc
     */
    public function getSynopsis(bool $short = false): string
    {
        return $this->synopsis[$short] ??= trim(sprintf('%s %s', $this->getName(), $this->getDefinition()->getSynopsis($short)));
    }

    /**
     * @inheritDoc
     */
    public function getDefinition(): InputDefinition
    {
        return $this->definition ??= new InputDefinition(...$this->getDefinitions());
    }

    /**
     * @inheritDoc
     */
    public function getConcrete(Input $input): InputConcrete
    {
        return $this->getDefinition()->resolve($input);
    }
}
