<?php

declare(strict_types=1);

namespace Loner\Console\Command;

use Loner\Console\Descriptor\Descriptor;
use Loner\Console\Input\Definition\Argument;
use Loner\Console\Input\Input;
use Loner\Console\Output\Output;

class HelpCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function getDefaultName(): string
    {
        return 'help';
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultDescription(): string
    {
        return 'Displays help for a command';
    }

    /**
     * @inheritDoc
     */
    public function getDefinitions(): array
    {
        return [
            new Argument('command', 0, 'The command name', $this->getName())
        ];
    }

    /**
     * @inheritDoc
     */
    public function run(Input $input, Output $output): int
    {
        $concrete = $this->getConcrete($input);
        $console = $this->getConsole();
        $command = $console->get($concrete->getArgument(0));
        $descriptor = new Descriptor($output);
        $descriptor->describeCommand($command);
        return 0;
    }
}
