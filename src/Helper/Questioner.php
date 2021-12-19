<?php

declare(strict_types=1);

namespace Loner\Console\Helper;

use Loner\Console\Exception\{MissingInputException, QuestionValidationException, RuntimeException};
use Loner\Console\Input\Input;
use Loner\Console\Output\Output;
use Loner\Console\Question\{ChoiceQuestion, Question};
use Loner\Console\Terminal;
use Throwable;

/**
 * 提问者
 *
 * @package Loner\Console\Helper
 */
class Questioner
{
    private static bool $stdinIsInteractive;

    /**
     * 初始化输出对象
     *
     * @param Output $output
     */
    public function __construct(private Output $output)
    {
    }

    /**
     * 向用户提问
     *
     * @param Question $question
     * @param Input|null $input
     * @return mixed
     * @throws RuntimeException
     * @throws MissingInputException
     * @throws QuestionValidationException
     */
    public function ask(Question $question, ?Input $input = null): mixed
    {
        $inputStream = $input->getStream() ?? STDIN;

        $validator = $question->getValidator();

        try {
            if ($validator === null) {
                return $this->doAsk($question, $inputStream);
            }

            $attempts = $question->getMaxAttempts();

            $throwable = null;

            while (null === $attempts || $attempts--) {
                try {
                    $result = $this->doAsk($question, $inputStream);
                } catch (RuntimeException $e) {
                    throw $e;
                }
                try {
                    return $validator($result);
                } catch (Throwable $throwable) {
                    $this->output->throwable($throwable);
                }
            }

            if ($throwable instanceof QuestionValidationException) {
                /** @var QuestionValidationException $throwable */
                throw $throwable;
            }

            throw new QuestionValidationException($throwable->getMessage(), $throwable->getCode(), $throwable->getPrevious());
        } catch (MissingInputException $e) {
            if (null === $default = $question->getDefault()) {
                throw $e;
            }

            if ($validator !== null) {
                return $validator($default);
            }

            if ($question instanceof ChoiceQuestion) {
                $choices = $question->getChoices();

                if (!$question->isMultiselect()) {
                    return $choices[$default] ?? $default;
                }

                $isTrim = $question->isTrim();

                $default = explode(',', $default);
                foreach ($default as $k => $v) {
                    if ($isTrim) {
                        $v = trim($v);
                    }
                    $default[$k] = $choices[$v] ?? $v;
                }
            }

            return $default;
        }
    }

    /**
     * 向用户提问一次
     *
     * @param Question $question
     * @param resource $inputStream
     * @return mixed
     * @throws RuntimeException
     * @throws MissingInputException
     */
    private function doAsk(Question $question, $inputStream): mixed
    {
        $this->writePrompt($question);

        // windows 的 cmd.exe 使用的代码页允许使用特殊字符
        function_exists('sapi_windows_cp_set') && @sapi_windows_cp_set(1252);

        if ($question->isHidden()) {
            try {
                $result = $this->getHiddenResponse($inputStream);
            } catch (RuntimeException $e) {
                if (!$question->isHiddenFallback()) {
                    throw $e;
                }
            }
        }

        if (!isset($result)) {
            $result = fgets($inputStream, 4096);
        }

        if ($question->isTrim()) {
            $result = trim($result);
        }

        if ($result === '') {
            $result = $question->getDefault();
        }

        $normalizer = $question->getNormalizer();
        return $normalizer === null ? $result : $normalizer($result);
    }

    /**
     * 输出问题提示
     *
     * @param Question $question
     */
    private function writePrompt(Question $question): void
    {
        $message = $question->getQuestion();

        if ($question instanceof ChoiceQuestion) {
            $this->output->writeln($message);
            $this->output->writeln(self::formatQuestionChoices($question, 'info'));
            $message = $question->getPrompt();
        }

        $this->output->write($message);
    }

    /**
     * 格式化问题选项并返回
     *
     * @param ChoiceQuestion $question
     * @param string $tag
     * @return array
     */
    private static function formatQuestionChoices(ChoiceQuestion $question, string $tag): array
    {
        $choices = $question->getChoices();

        // 选项下表长度上限
        $keyMaxWidth = array_reduce(array_keys($choices), fn($max, $key) => max($max, strlen((string)$key)), 0);

        $messages = [];

        // 选项下标对齐并打上标签
        foreach ($choices as $key => $value) {
            $messages[] = sprintf("[<{$tag}>%s</{$tag}>] %s", str_pad((string)$key, $keyMaxWidth, ' ', STR_PAD_BOTH), $value);
        }

        return $messages;
    }

    /**
     * 获取隐藏的用户响应
     *
     * @param resource $inputStream
     * @return string
     * @throws RuntimeException
     * @throws MissingInputException
     */
    private function getHiddenResponse($inputStream): string
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $exe = __DIR__ . '/../Resources/bin/hiddeninput.exe';

            // handle code running from a phar
            if ('phar:' === substr(__FILE__, 0, 5)) {
                $tmpExe = sys_get_temp_dir() . '/hiddeninput.exe';
                copy($exe, $tmpExe);
                $exe = $tmpExe;
            }

            $value = shell_exec('"' . $exe . '"');
            $this->output->writeln();

            if (isset($tmpExe)) {
                unlink($tmpExe);
            }

            return $value;
        }

        if (Terminal::hasSttyAvailable()) {
            $sttyMode = shell_exec('stty -g');
            shell_exec('stty -echo');
            $value = fgets($inputStream, 4096);
            shell_exec(sprintf('stty %s', $sttyMode));
        } else {
            if ($this->isInteractiveInput($inputStream)) {
                throw new RuntimeException('Unable to hide the response.');
            }

            $value = fgets($inputStream, 4096);
        }

        if (false === $value) {
            throw new MissingInputException('Aborted.');
        }

        $this->output->writeln();

        return $value;
    }

    /**
     * 判断是否可交互输入流
     *
     * @param resource $inputStream
     * @return bool
     */
    private function isInteractiveInput($inputStream): bool
    {
        if ('php://stdin' !== (stream_get_meta_data($inputStream)['uri'] ?? null)) {
            return false;
        }

        if (isset(self::$stdinIsInteractive)) {
            return self::$stdinIsInteractive;
        }

        if (function_exists('stream_isatty')) {
            return self::$stdinIsInteractive = stream_isatty(fopen('php://stdin', 'r'));
        }

        if (function_exists('posix_isatty')) {
            return self::$stdinIsInteractive = posix_isatty(fopen('php://stdin', 'r'));
        }

        if (!function_exists('exec')) {
            return self::$stdinIsInteractive = true;
        }

        exec('stty 2> /dev/null', $output, $status);

        return self::$stdinIsInteractive = 1 !== $status;
    }
}
