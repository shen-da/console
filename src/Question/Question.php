<?php

declare(strict_types=1);

namespace Loner\Console\Question;

use Loner\Console\Exception\{InvalidArgumentException, LogicException};

/**
 * 问题
 *
 * @package Loner\Console\Question
 */
class Question
{
    /**
     * 用户响应是否隐藏
     *
     * @var bool
     */
    private bool $hidden = false;

    /**
     * 若用户响应无法隐藏，是否回退为非隐藏问题
     *
     * @var bool
     */
    private bool $hiddenFallback = true;

    /**
     * 用户响应是否修剪
     *
     * @var bool
     */
    private bool $trim = true;

    /**
     * 最大尝试次数
     *
     * @var int|null
     */
    private ?int $attempts = null;

    /**
     * 响应规范化处理器
     *
     * @var callable|null
     */
    private $normalizer;

    /**
     * 验证程序
     *
     * @var callable|null
     */
    private $validator;

    /**
     * 初始化问题及默认回答
     *
     * @param string $question
     * @param mixed|null $default
     */
    public function __construct(private string $question, private mixed $default = null)
    {
    }

    /**
     * 返回问题内容
     *
     * @return string
     */
    public function getQuestion(): string
    {
        return $this->question;
    }

    /**
     * 获取默认回答
     *
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * 返回是否必须隐藏用户响应
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * 设置是否必须隐藏用户响应
     *
     * @param bool $hidden
     * @return $this
     * @throws LogicException
     */
    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * 若无法隐藏用户响应，是否回退为非隐藏问题
     *
     * @return bool
     */
    public function isHiddenFallback(): bool
    {
        return $this->hiddenFallback;
    }

    /**
     * 设置若无法隐藏用户响应，是否回退为非隐藏问题
     *
     * @param bool $fallback
     *
     * @return $this
     */
    public function setHiddenFallback(bool $fallback): self
    {
        $this->hiddenFallback = $fallback;
        return $this;
    }

    /**
     * 返回用户响应是否可修剪（去空字符）
     *
     * @return bool
     */
    public function isTrim(): bool
    {
        return $this->trim;
    }

    /**
     * 设置用户响应是否可修剪
     *
     * @param bool $trim
     * @return $this
     */
    public function setTrim(bool $trim): self
    {
        $this->trim = $trim;
        return $this;
    }

    /**
     * 获取最大尝试次数，null 表示不限次数
     *
     * @return int|null
     */
    public function getMaxAttempts(): ?int
    {
        return $this->attempts;
    }

    /**
     * 设置最大尝试次数，null 表示不限次数
     *
     * @param int|null $attempts
     * @return $this
     */
    public function setMaxAttempts(?int $attempts): self
    {
        if (null !== $attempts) {
            if ($attempts < 1) {
                throw new InvalidArgumentException('Maximum number of attempts must be a positive value.');
            }
        }

        $this->attempts = $attempts;
        return $this;
    }

    /**
     * 返回响应规范化处理器
     *
     * @return callable|null
     */
    public function getNormalizer(): ?callable
    {
        return $this->normalizer;
    }

    /**
     * 设置响应规范化处理器
     *
     * @param callable|null $normalizer
     * @return $this
     */
    public function setNormalizer(callable $normalizer = null): self
    {
        $this->normalizer = $normalizer;
        return $this;
    }

    /**
     * 获取问题的验证程序
     *
     * @return callable|null
     */
    public function getValidator(): ?callable
    {
        return $this->validator;
    }

    /**
     * 设置问题的验证程序
     *
     * @param callable|null $validator
     * @return $this
     */
    public function setValidator(callable $validator = null): self
    {
        $this->validator = $validator;
        return $this;
    }
}
