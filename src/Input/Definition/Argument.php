<?php

declare(strict_types=1);

namespace Loner\Console\Input\Definition;

use Loner\Console\Exception\{InvalidArgumentException, LogicException};

/**
 * 输入定义参数
 *
 * @package Loner\Console\Input\Definition
 */
class Argument
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
     * 默认值
     *
     * @var string[]|string|null
     */
    private array|string|null $default;

    /**
     * 初始化参数信息
     *
     * @param string $name
     * @param int $mode
     * @param string $description
     * @param string ...$defaults
     */
    public function __construct(private string $name, private int $mode = 0, private string $description = '', string ...$defaults)
    {
        $this->checkName();
        $this->checkMode();

        $this->setDefault(...$defaults);
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
        if (!preg_match('/^\w+$/', $this->name)) {
            throw new InvalidArgumentException('Argument name must start with a letter and consist of letters, numbers, and underscores.');
        }
    }

    /**
     * 检测模式
     *
     * @throws InvalidArgumentException
     */
    private function checkMode(): void
    {
        if ($this->mode > (self::REQUIRED | self::COMPLEX) || $this->mode < 0) {
            throw new InvalidArgumentException(sprintf('Argument mode "%s" is not valid.', $this->mode));
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
        if ($this->isRequired() && !empty($defaults)) {
            throw new LogicException('Cannot set a default value when the argument using mode REQUIRED.');
        }

        if (!$this->isComplex() && count($defaults) > 1) {
            throw new LogicException('Multiple default values can only be set when the argument using mode COMPLEX.');
        }

        $this->default = $this->isComplex() ? $defaults ?: null : $defaults[0] ?? null;
    }
}
