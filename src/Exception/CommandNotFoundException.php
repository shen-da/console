<?php

declare(strict_types=1);

namespace Loner\Console\Exception;

/**
 * 异常：命令未找到
 *
 * @package Loner\Console\Exception
 */
class CommandNotFoundException extends \InvalidArgumentException implements ExceptionInterface
{
}
