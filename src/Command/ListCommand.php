<?php

declare(strict_types=1);

namespace Loner\Console\Command;

use Loner\Console\Input\Definition\Argument;
use Loner\Console\Input\Input;
use Loner\Console\Descriptor\Descriptor;
use Loner\Console\Output\Output;

/**
 * 列表命令
 *
 * @package Loner\Console\Command
 */
class ListCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function getDefaultName(): string
    {
        return 'list';
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultDescription(): string
    {
        return 'Lists commands';
    }

    /**
     * @inheritDoc
     */
    public function getDefinitions(): array
    {
        return [
            new Argument('prefix', 0, 'The prefix of command name or namespace', '')
        ];
    }

    /**
     * @inheritDoc
     */
    public function run(Input $input, Output $output): int
    {
        $concrete = $this->getConcrete($input);
        $prefix = $concrete->getArgument(0);
        $console = $this->getConsole();
        $descriptor = new Descriptor($output);
        $descriptor->describeCommands($console, $prefix);
        return 0;
    }
}
