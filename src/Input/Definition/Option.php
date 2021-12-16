<?php

declare(strict_types=1);

namespace Loner\Console\Input\Definition;

use Loner\Console\Exception\{InvalidArgumentException, LogicException};

/**
 * 选项
 *
 * @package Loner\Console\Input\Definition
 */
class Option
{
    /**
     * 模式：复合值（可多值）
     */
    public const COMPLEX = 0b1;

    /**
     * 模式：必须提供值
     */
    public const REQUIRED = 0b10;

    /**
     * 模式：值可选
     */
    public const OPTIONAL = 0b100;

    /**
     * 默认值
     *
     * @var string[]|string|null
     */
    private array|string|null $default;

    /**
     * 初始化选项信息
     *
     * @param string $name
     * @param string|null $shortcut
     * @param int $mode
     * @param string $description
     * @param string ...$defaults
     */
    public function __construct(
        private string $name,
        private ?string $shortcut = null,
        private int $mode = 0,
        private string $description = '',
        string ...$defaults
    )
    {
        $this->checkName();

        if ($this->shortcut !== null) {
            $this->checkShortcut();
        }

        $this->checkMode();

        $this->setDefault(...$defaults);
    }

    /**
     * 返回快捷名
     *
     * @return string|null
     */
    public function getShortcut(): ?string
    {
        return $this->shortcut;
    }

    /**
     * 返回是否可选值
     *
     * @return bool
     */
    public function isOptional(): bool
    {
        return self::OPTIONAL === (self::OPTIONAL & $this->mode);
    }

    /**
     * 返回是否可接受值
     *
     * @return bool
     */
    public function acceptValue(): bool
    {
        return $this->isRequired() || $this->isOptional();
    }

    /**
     * 返回名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 返回描述文本
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * 返回默认值
     *
     * @return string[]|string|null
     */
    public function getDefault(): array|string|null
    {
        return $this->default;
    }

    /**
     * 返回是否必须提供值
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return self::REQUIRED === (self::REQUIRED & $this->mode);
    }

    /**
     * 返回是否支持多值
     *
     * @return bool
     */
    public function isComplex(): bool
    {
        return self::COMPLEX === (self::COMPLEX & $this->mode);
    }

    /**
     * 检测名称
     *
     * @throws InvalidArgumentException
     */
    private function checkName(): void
    {
        if (!preg_match('/^[^\s=]+$/', $this->name)) {
            throw new InvalidArgumentException('Option name must be a non empty string without "=" and spaces.');
        }
    }

    /**
     * 检测快捷名
     *
     * @throws InvalidArgumentException
     */
    private function checkShortcut(): void
    {
        if (!preg_match('/^[^\s-]$/', $this->shortcut)) {
            throw new InvalidArgumentException('Option shortcut name must be non empty single character and cannot be "-".');
        }
    }

    /**
     * 检测模式
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    private function checkMode(): void
    {
        if ($this->mode > (self::REQUIRED | self::COMPLEX | self::OPTIONAL) || $this->mode < 0) {
            throw new InvalidArgumentException(sprintf('Option mode "%s" is not valid.', $this->mode));
        }

        if ($this->isRequired() && $this->isOptional()) {
            throw new LogicException('Option modes REQUIRED and OPTIONAL cannot coexist.');
        }

        if ($this->isComplex() && !$this->acceptValue()) {
            throw new LogicException('Option mode COMPLEX cannot exist without REQUIRED or OPTIONAL.');
        }
    }

    /**
     * 设置默认值
     *
     * @param string ...$defaults
     * @throws LogicException
     */
    private function setDefault(string ...$defaults): void
    {
        if ($this->isOptional() xor !empty($defaults)) {
            throw new LogicException('If and only if the option mode contains OPTIONAL, the default value is not empty.');
        }

        if ($this->isRequired() && !empty($defaults)) {
            throw new LogicException('Cannot set a default value when the option using mode REQUIRED.');
        }

        if (!$this->isComplex() && count($defaults) > 1) {
            throw new LogicException('Multiple default values can only be set when the option using mode COMPLEX.');
        }

        $this->default = $this->isComplex() ? $defaults ?: null : $defaults[0] ?? null;
    }
}
