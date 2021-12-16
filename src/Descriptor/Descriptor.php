<?php

declare(strict_types=1);

namespace Loner\Console\Descriptor;

use Loner\Console\Command\CommandInterface;
use Loner\Console\Console;
use Loner\Console\Input\Definition\{Argument, Option};
use Loner\Console\Input\InputDefinition;
use Loner\Console\Output\Output;

/**
 * 描述器
 *
 * @package Loner\Console\Descriptor
 */
class Descriptor
{
    /**
     * 初始化输出对象
     *
     * @param Output $output
     */
    public function __construct(private Output $output)
    {
    }

    /**
     * 描述控制台实例
     *
     * @param Console $console
     */
    public function describeConsole(Console $console): void
    {
        $this->output->writeln();
        $this->output->writeln(sprintf('Loner Console <info>v%s</info>', Console::VERSION));

        $this->output->writeln();
        $this->output->writeln('<comment>Usages:</comment>');
        $this->output->writeln(['  command [arguments] [options]', '  command [options] [--] [arguments]']);

        $this->describeOptions($console->getDefinition()->getOptions());

        $this->describeCommands($console);
    }

    /**
     * 描述输入定义
     *
     * @param InputDefinition $definition
     */
    public function describeDefinition(InputDefinition $definition): void
    {
        $this->describeArguments($definition->getArguments());
        $this->describeOptions($definition->getOptions());
    }

    /**
     * 描述位置参数
     *
     * @param Argument[] $arguments
     */
    public function describeArguments(array $arguments): void
    {
        $argumentWidth = self::calculateAlignWidthForArguments($arguments);

        if ($argumentWidth > 0) {
            $this->output->writeln();
            $this->output->writeln('<comment>Arguments:</comment>');
            foreach ($arguments as $argument) {
                $this->describeArgument($argument, $argumentWidth);
            }
        }
    }

    /**
     * 描述位置参数
     *
     * @param Argument $argument
     * @param int|null $alignWidth
     */
    public function describeArgument(Argument $argument, int $alignWidth = null): void
    {
        if (null === $default = $argument->getDefault()) {
            $default = '';
        } else {
            $default = $argument->isComplex() ? join(' ', $default) : $default;
            $default = self::decorateDefault($default);
        }

        $width = self::normalizeArgumentNameSpacing($argument);
        if ($alignWidth === null) {
            $spacing = 0;
            $alignWidth = $width;
        } else {
            $spacing = $alignWidth - $width;
        }

        $space = $spacing > 0 ? str_repeat(' ', $spacing) : '';

        $this->output->writeln(sprintf('  <info>%s</info>  %s%s%s',
            $argument->getName(),
            $space,
            preg_replace('/\s*[\r\n]/', "\n" . str_repeat(' ', $alignWidth + 4), $argument->getDescription()),
            $default
        ));
    }

    /**
     * 描述选项参数
     *
     * @param Option[] $options
     */
    public function describeOptions(array $options): void
    {
        $optionWidth = self::calculateAlignWidthForOptions($options);

        if ($optionWidth > 0) {
            $this->output->writeln();
            $this->output->writeln('<comment>Options:</comment>');
            foreach ($options as $option) {
                $this->describeOption($option, $optionWidth);
            }
        }
    }

    /**
     * 描述选项参数
     *
     * @param Option $option
     * @param int|null $alignWidth
     */
    public function describeOption(Option $option, int $alignWidth = null): void
    {
        if (null === $default = $option->getDefault()) {
            $default = '';
        } else {
            $default = $option->isComplex() ? join(' ', $default) : $default;
            $default = self::decorateDefault($default);
        }

        $width = self::normalizeOptionNameSpacing($option);
        if ($alignWidth === null) {
            $spacing = 0;
            $alignWidth = $width;
        } else {
            $spacing = $alignWidth - $width;
        }

        if ($option->acceptValue()) {
            $format = $option->isRequired() ? '=%s' : '[=%s]';
            $value = sprintf($format, strtoupper($option->getName()));
        } else {
            $value = '';
        }

        $synopsis = sprintf('%s%s',
            $option->getShortcut() ? sprintf('-%s, ', $option->getShortcut()) : '    ',
            sprintf('--%s%s', $option->getName(), $value)
        );

        $space = $spacing > 0 ? str_repeat(' ', $spacing) : '';

        $this->output->writeln(sprintf('  <info>%s</info>  %s%s%s%s',
            $synopsis,
            $space,
            preg_replace('/\s*[\r\n]/', "\n" . str_repeat(' ', $alignWidth + 4), $option->getDescription()),
            $default,
            $option->isComplex() ? ' <comment>(multiple values allowed)</comment>' : ''
        ));
    }

    /**
     * 描述命令列表（根据前缀做命令名称或命名空间的筛选）
     *
     * @param Console $console
     * @param string $prefix
     */
    public function describeCommands(Console $console, string $prefix = ''): void
    {
        $this->output->writeln();

        $commands = $console->getCommands($prefix);

        if (empty($commands)) {
            $this->output->writeln('<comment>No commands</comment>');
        } else {
            $this->output->writeln('<comment>Commands:</comment>');
            $alignWidth = self::calculateAlignWidthForCommands($commands);
            foreach ($commands as $namespace => $commandList) {
                $namespace === '' || $this->output->writeln(sprintf(' <comment>%s</comment>', $namespace));
                foreach ($commandList as $name => $command) {
                    $space = str_repeat(' ', $alignWidth - strlen($name));
                    $this->output->writeln(sprintf('  <info>%s</info>  %s%s', $name, $space, $command->getDescription()));
                }
            }
        }
    }

    /**
     * 描述命令
     *
     * @param CommandInterface $command
     */
    public function describeCommand(CommandInterface $command): void
    {
        if ('' !== $description = $command->getDescription()) {
            $this->output->writeln();
            $this->output->writeln('<comment>Description:</comment>');
            $this->output->writeln('  ' . $description);
        }

        $this->output->writeln();
        $this->output->writeln('<comment>Usages:</comment>');
        $this->output->writeln('  ' . $command->getSynopsis(true));
        foreach ($command->getUsages() as $usage) {
            $this->output->writeln('  ' . $usage);
        }

        $definition = $command->getDefinition();
        if ($definition->getOptions() || $definition->getArguments()) {
            $this->describeDefinition($definition);
        }
    }

    /**
     * 计算命令的名称对齐宽度
     *
     * @param CommandInterface[][] $commands
     * @return int
     */
    private static function calculateAlignWidthForCommands(array $commands): int
    {
        return array_reduce($commands, fn($alignWidth, $commands) => array_reduce($commands, fn($alignWidth, $command) => max($alignWidth, strlen($command->getName())), $alignWidth), 0);
    }

    /**
     * 计算位置参数的名称对齐宽度
     *
     * @param array $arguments
     * @return int
     */
    private static function calculateAlignWidthForArguments(array $arguments): int
    {
        return array_reduce($arguments, fn($alignWidth, Argument $argument) => max($alignWidth, self::normalizeArgumentNameSpacing($argument)), 0);
    }

    /**
     * 计算选项的名称对齐宽度
     *
     * @param Option[] $options
     * @return int
     */
    private static function calculateAlignWidthForOptions(array $options): int
    {
        return array_reduce($options, fn($alignWidth, Option $option) => max($alignWidth, self::normalizeOptionNameSpacing($option)), 0);
    }

    /**
     * 获取位置参数名标准占位
     *
     * @param Argument $argument
     * @return int
     */
    private static function normalizeArgumentNameSpacing(Argument $argument): int
    {
        return strlen($argument->getName());
    }

    /**
     * 获取选项标准占位
     *
     * @param Option $option
     * @return int
     */
    private static function normalizeOptionNameSpacing(Option $option): int
    {
        $width = strlen($option->getName());
        // 接收值加=NAME
        if ($option->acceptValue()) {
            $width = $width * 2 + 1;
            // 可选参数加[]
            if ($option->isOptional()) {
                $width += 2;
            }
        }
        return $width + 6;
    }

    /**
     * 返回装饰后的默认值
     *
     * @param string $default
     * @return string
     */
    private static function decorateDefault(string $default): string
    {
        return sprintf('<comment> [default: \'%s\']</comment>', $default);
    }
}
