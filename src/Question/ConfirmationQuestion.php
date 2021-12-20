<?php

declare(strict_types=1);

namespace Loner\Console\Question;

/**
 * 确定性问题
 *
 * @package Loner\Console\Question
 *
 * @method bool getDefault()
 */
class ConfirmationQuestion extends Question
{
    /**
     * 初始化问题及默认回答
     *
     * @param string $question
     * @param bool $default
     */
    public function __construct(string $question, bool $default = true)
    {
        parent::__construct($question, $default);

        $this->setNormalizer($this->defaultNormalizer());
    }

    /**
     * 默认标准处理器
     *
     * @return callable
     */
    private function defaultNormalizer(): callable
    {
        $default = $this->getDefault();
        return fn($answer) => is_bool($answer) ? $answer : ($answer === '' ? $default : in_array($answer, ['y', 'yes']));
    }
}
