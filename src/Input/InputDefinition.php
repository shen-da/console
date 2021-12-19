<?php

declare(strict_types=1);

namespace Loner\Console\Input;

use Loner\Console\Exception\{DefinitionResolvedException, InvalidArgumentException, LogicException};
use Loner\Console\Input\Definition\{Argument, Option};

/**
 * 输入定义
 *
 * @package Loner\Console\Input
 */
class InputDefinition
{
    /**
     * 必须位置参数个数
     *
     * @var int
     */
    private int $requiredArgumentCount = 0;

    /**
     * 是否含多值位置参数
     *
     * @var bool
     */
    private bool $hasComplexArgument = false;

    /**
     * 是否含值可选位置参数
     *
     * @var bool
     */
    private bool $hasOptionalArgument = false;

    /**
     * 位置参数列表
     *
     * @var Argument[] [$name => Argument]
     */
    private array $arguments = [];

    /**
     * 选项列表
     *
     * @var Option[] [$name => Option]
     */
    private array $options = [];

    /**
     * 选项快捷名列表
     *
     * @var string[] [$shortcut => $name]
     */
    private array $optionShortcuts = [];

    /**
     * 初始化定义信息
     *
     * @param array|null $definitions
     */
    public function __construct(Argument|Option ...$definitions)
    {
        empty($definitions) || $this->setDefinitions(...$definitions);
    }

    /**
     * 重新设置参数
     *
     * @param Argument|Option ...$definitions
     */
    public function setDefinitions(Argument|Option ...$definitions): void
    {
        $arguments = [];
        $options = [];

        foreach ($definitions as $definition) {
            if ($definition instanceof Argument) {
                $arguments[] = $definition;
            } else {
                $options[] = $definition;
            }
        }

        $this->setArguments(...$arguments);
        $this->setOptions(...$options);
    }

    /**
     * 获取位置参数列表
     *
     * @return Argument[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * 重新设置参数
     *
     * @param Argument ...$arguments
     */
    public function setArguments(Argument ...$arguments): void
    {
        $this->arguments = [];
        $this->requiredArgumentCount = 0;
        $this->hasComplexArgument = false;
        $this->hasOptionalArgument = false;
        array_walk($arguments, [$this, 'addArgument']);
    }

    /**
     * 添加参数
     *
     * @param Argument $argument
     */
    public function addArgument(Argument $argument): void
    {
        $name = $argument->getName();

        if (isset($this->arguments[$name])) {
            throw new LogicException(sprintf('An argument with name "%s" already exists.', $name));
        }

        if ($this->hasComplexArgument) {
            throw new LogicException('Cannot add an argument after an array argument.');
        }

        if ($argument->isRequired() && $this->hasOptionalArgument) {
            throw new LogicException('Cannot add a required argument after an optional one.');
        }

        if ($argument->isComplex()) {
            $this->hasComplexArgument = true;
        }

        if ($argument->isRequired()) {
            ++$this->requiredArgumentCount;
        } else {
            $this->hasOptionalArgument = true;
        }

        $this->arguments[$name] = $argument;
    }

    /**
     * 获取选项列表
     *
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * 返回指定名称的选项
     *
     * @param string $name
     * @return Option
     * @throws InvalidArgumentException
     */
    public function getOption(string $name): Option
    {
        if (!$this->hasOption($name)) {
            throw new InvalidArgumentException(sprintf('The "--%s" option does not exist.', $name));
        }

        return $this->options[$name];
    }

    /**
     * 判断是否含有指定选项
     *
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * 判断是否有指定快捷名的选项
     *
     * @param string $name
     * @return bool
     */
    public function hasShortcut(string $name): bool
    {
        return isset($this->shortcuts[$name]);
    }

    /**
     * 重新设置选项
     *
     * @param Option ...$options
     */
    public function setOptions(Option ...$options): void
    {
        $this->options = [];
        $this->optionShortcuts = [];
        array_walk($options, [$this, 'addOption']);
    }

    /**
     * 添加选项
     *
     * @param Option $option
     */
    public function addOption(Option $option): void
    {
        $name = $option->getName();

        if (isset($this->options[$name])) {
            throw new LogicException(sprintf('An option named "%s" already exists.', $name));
        }

        $shortcut = $option->getShortcut();

        if ($shortcut !== null) {
            if (isset($this->optionShortcuts[$shortcut])) {
                throw new LogicException(sprintf('An option with shortcut "%s" already exists.', $shortcut));
            }
            $this->optionShortcuts[$shortcut] = $name;
        }

        $this->options[$name] = $option;
    }

    /**
     * 获取摘要
     *
     * @param bool $short
     * @return string
     */
    public function getSynopsis(bool $short = false): string
    {
        $optionSynopses = $this->getSynopsisOfOptions($short);
        $argumentSynopses = $this->getSynopsisOfArguments();

        return $optionSynopses === '' || $argumentSynopses === ''
            ? $optionSynopses . $argumentSynopses
            : $optionSynopses . ' [--] ' . $argumentSynopses;
    }

    /**
     * 获取输入实体（严格模式下，不能有多余参数）
     *
     * @param Input $input
     * @param bool $strict
     * @return InputConcrete
     * @throws DefinitionResolvedException
     */
    public function resolve(Input $input, bool $strict = true): InputConcrete
    {
        $givenArguments = $input->getArguments();
        $givenArgumentCount = count($givenArguments);

        // 提供常规参数不足，抛出异常
        if ($givenArgumentCount < $this->requiredArgumentCount) {
            throw new DefinitionResolvedException(sprintf(
                'At least %d arguments are required and only %d are provided.',
                $this->requiredArgumentCount, $givenArgumentCount
            ));
        }

        $allArguments = $this->getArguments();
        $allArgumentCount = count($allArguments);

        // 严格模式下，提供多余常规参数，抛出异常
        if ($strict && $givenArgumentCount > $allArgumentCount && $this->hasComplexArgument === false) {
            throw new DefinitionResolvedException(
                sprintf(
                    'Only %d arguments are required, but %s are provided.',
                    $allArgumentCount, $givenArgumentCount
                )
            );
        }

        $position = 0;
        $arguments = [];
        foreach ($allArguments as $name => $argument) {
            if ($position < $givenArgumentCount) {
                $arguments[] = $argument->isComplex()
                    ? $input->getComplexArgumentValues($position++)
                    : $input->getArgumentValue($position++);
            } elseif (null !== $default = $argument->getDefault()) {
                $arguments[] = $default;
            }
        }

        $longOptions = $input->getOptions();
        $shortOptions = $input->getShortOptions();

        $options = [];

        foreach ($this->options as $name => $option) {
            $hasOptions = isset($longOptions[$name]);
            $shortcut = $option->getShortcut();
            $hasShort = $shortcut !== null && isset($shortOptions[$shortcut]);

            if (!$hasOptions && !$hasShort) {
                // 若未提供该选项，且该选项有默认值，取默认值
                if (null !== $default = $option->getDefault()) {
                    $options[$name] = $default;
                }
                continue;
            }

            // 合并选项值列表
            if ($hasOptions) {
                if ($shortcut) {
                    $values = array_merge($longOptions[$name], $shortOptions[$shortcut]);
                    unset($shortOptions[$shortcut]);
                } else {
                    $values = $longOptions[$name];
                }
                unset($longOptions[$name]);
            } else {
                $values = $shortOptions[$shortcut];
                unset($shortOptions[$shortcut]);
            }

            if (empty($values)) {
                // 若提供选项不带值，且该选项须提供值，抛出异常
                if ($option->isRequired()) {
                    throw new DefinitionResolvedException(sprintf('Option "%s" must provide a value.', $name));
                }
                $options[$name] = $option->getDefault();
            } else {
                // 若提供选项及值，且该选项不接受值，抛出异常
                if (!$option->acceptValue()) {
                    throw new DefinitionResolvedException(sprintf('Option "%s" does not accept values.', $name));
                }

                // 若提供选项及多值，且该选项非复合型，抛出异常
                if (!$option->isComplex() && count($values) > 1) {
                    throw new DefinitionResolvedException(sprintf('Option "%s" cannot accept multiple values.', $name));
                }

                $options[$name] = $option->isComplex() ? $values : $values[0];
            }
        }

        // 严格模式下，若提供多余选项，抛出异常
        if ($strict) {
            foreach ($longOptions as $name => $options) {
                throw new DefinitionResolvedException(sprintf('Invalid option: "--%s".', $name));
            }
            foreach ($shortOptions as $shortcut => $options) {
                throw new DefinitionResolvedException(sprintf('Invalid option: "-%s".', $shortcut));
            }
        }

        return new InputConcrete($arguments, $options);
    }

    /**
     * 获取位置参数摘要
     *
     * @return string
     */
    private function getSynopsisOfArguments(): string
    {
        $arguments = $this->getArguments();

        if (empty($arguments)) {
            return '';
        }

        $synopses = [];

        $tail = '';
        foreach ($this->arguments as $name => $argument) {
            $synopsis = '<' . $name . '>';
            if ($argument->isComplex()) {
                $synopsis .= '...';
            }

            if (!$argument->isRequired()) {
                $synopsis = '[' . $synopsis;
                $tail .= ']';
            }

            $synopses[] = $synopsis;
        }

        return join(' ', $synopses) . $tail;
    }

    /**
     * 获取选项摘要
     *
     * @param bool $short
     * @return string
     */
    private function getSynopsisOfOptions(bool $short): string
    {
        $options = $this->getOptions();

        if (empty($options)) {
            return '';
        }

        if ($short) {
            return '[options]';
        }

        $synopses = [];

        foreach ($options as $name => $option) {
            $value = $option->acceptValue()
                ? $option->isOptional()
                    ? sprintf(' %s%s%s', '[', strtoupper($name), ']')
                    : sprintf(' %s', strtoupper($name))
                : '';

            $shortcut = $option->getShortcut() ? sprintf('-%s|', $option->getShortcut()) : '';
            $synopses[] = sprintf('[%s--%s%s]', $shortcut, $name, $value);
        }

        return join(' ', $synopses);
    }
}
