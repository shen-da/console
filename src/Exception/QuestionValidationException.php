<?php

declare(strict_types=1);

namespace Loner\Console\Exception;

/**
 * 异常：问题回答验证失败
 *
 * @package Loner\Console\Exception
 */
class QuestionValidationException extends InvalidArgumentException implements ExceptionInterface
{
}
