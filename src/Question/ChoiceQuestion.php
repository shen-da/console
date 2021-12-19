<?php

declare(strict_types=1);

namespace Loner\Console\Question;

use LogicException;
use Loner\Console\Exception\QuestionValidationException;

/**
 * 选择性问题
 *
 * @package Loner\Console\Question
 */
class ChoiceQuestion extends Question
{
    /**
     * 是否多选
     *
     * @var bool
     */
    private bool $multiselect = false;

    /**
     * 提示
     *
     * @var string
     */
    private string $prompt = '> ';

    /**
     * 初始化问题、选项、默认回答
     *
     * @param string $question
     * @param array $choices
     * @param mixed|null $default
     */
    public function __construct(private string $question, private array $choices, private mixed $default = null)
    {
        if (!$choices) {
            throw new LogicException('Choice question must have at least 1 choice available.');
        }

        parent::__construct($question, $default);

        $this->setValidator($this->defaultValidator());
    }

    /**
     * 返回可用的选项
     *
     * @return array
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * 返回选项是否为多选
     *
     * @return bool
     */
    public function isMultiselect(): bool
    {
        return $this->multiselect;
    }

    /**
     * 设置选项是否为多选
     *
     * @param bool $multiselect
     * @return $this
     */
    public function setMultiselect(bool $multiselect): self
    {
        $this->multiselect = $multiselect;
        $this->setValidator($this->defaultValidator());
        return $this;
    }

    /**
     * 获取选择提示
     *
     * @return string
     */
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    /**
     * 设置选项提示
     *
     * @param string $prompt
     * @return $this
     */
    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    /**
     * 默认验证器
     *
     * @return callable
     */
    private function defaultValidator(): callable
    {
        $choices = $this->choices;
        $multiselect = $this->multiselect;

        return function ($selected) use ($choices, $multiselect) {
            // 多选以“,”分割
            $selectedChoices = $multiselect ? explode(',', $selected) : [$selected];

            // 剔除空白字符
            if ($this->isTrim()) {
                $selectedChoices = array_map(fn($value) => trim($value), $selectedChoices);
            }

            $multiselectChoices = [];

            foreach ($selectedChoices as $value) {

                $result = null;

                foreach ($choices as $k => $v) {
                    if ($value == $k || $value == $v) {
                        $result = $k;
                        break;
                    }
                }

                if ($result === null) {
                    throw new QuestionValidationException(sprintf('Value "%s" is invalid.', $value));
                }

                $multiselectChoices[] = $result;
            }

            return $multiselect ? $multiselectChoices : current($multiselectChoices);
        };
    }
}
