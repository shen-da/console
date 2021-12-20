<?php

declare(strict_types=1);

namespace Loner\Console;

use Loner\Console\Command\{CommandInterface, HelpCommand, ListCommand};
use Loner\Console\Helper\Descriptor;
use Loner\Console\Exception\{CommandNotFoundException, DefinitionResolvedException, QuestionValidationException};
use Loner\Console\Input\{Definition\Option, InputDefinition, Input};
use Loner\Console\Output\Output;
use Throwable;

/**
 * 控制台
 *
 * @package Loner\Console
 */
class Console
{
    /**
     * 版本号
     */
    public const VERSION = '1.0.3';

    /**
     * 命令库
     *
     * @var CommandInterface[][] [命令空间 => [命令名 => 命令对象]]
     */
    private array $commands = [];

    /**
     * 定义
     *
     * @var InputDefinition
     */
    private InputDefinition $definition;

    /**
     * 初始化控制台实例
     */
    public function __construct()
    {
        $this->add(new HelpCommand());
        $this->add(new ListCommand());
    }

    /**
     * 添加命令
     *
     * @param CommandInterface $command
     */
    public function add(CommandInterface $command): void
    {
        $command->setConsole($this);

        $namespace = $command->getNamespace();
        $name = $command->getName();

        if (isset($this->commands[$namespace])) {
            $commands = &$this->commands[$namespace];
            if (!isset($commands[$name])) {
                $commands[$name] = $command;
                ksort($commands);
            }
        } else {
            $this->commands[$namespace] = [$name => $command];
            ksort($this->commands);
        }
    }

    /**
     * 判断命令是否存在
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        foreach ($this->commands as $namespace => $commands) {
            if (isset($commands[$name])) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取指定命令
     *
     * @param string $name
     * @return CommandInterface
     */
    public function get(string $name): CommandInterface
    {
        foreach ($this->commands as $commands) {
            if (isset($commands[$name])) {
                return $commands[$name];
            }
        }

        throw new CommandNotFoundException(sprintf('Command "%s" is not defined.', $name));
    }

    /**
     * 启动控制台程序
     *
     * @param Input $input
     * @param Output $output
     * @return int
     */
    public function run(Input $input, Output $output): int
    {
        $definition = $this->getDefinition();

        $hasCommand = $input->hasArgument(0);

        try {
            $concrete = $definition->resolve($input, !$hasCommand);
            $this->determineOutputMode($output, $concrete->getOption('output'));
        } catch (DefinitionResolvedException $e) {
            $this->setOutMode($output);
            $this->describe($output);
            return self::throwable($e, $output);
        }

        // 没有命令，输出控制台描述
        if (!$hasCommand) {
            $this->describe($output);
            return 0;
        }

        $name = $input->getArgumentValue(0);
        try {
            $command = $this->get($name);
        } catch (CommandNotFoundException $e) {
            $this->describe($output);
            return self::throwable($e, $output);
        }

        try {
            // 执行命令
            $input = $input->clone(1, ...$definition->getOptions());
            $code = $command->run($input, $output);
            return self::normalizeCode($code);
        } catch (Throwable $throwable) {
            // 不再输出问答验证异常的信息（验证时已有输出）
            if ($throwable instanceof QuestionValidationException) {
                $output = null;
            } // 定义解析异常，输出命令详情
            elseif ($throwable instanceof DefinitionResolvedException) {
                (new Descriptor($output))->describeCommand($this->get('help'));
            } elseif ($throwable instanceof CommandNotFoundException) {
                $this->describe($output);
            }

            return self::throwable($throwable, $output);
        }
    }

    /**
     * 获取自定义指令列表
     *
     * @param string $prefix
     * @return CommandInterface[]
     */
    public function getCommands(string $prefix = ''): array
    {
        if ($prefix === '') {
            return $this->commands;
        }

        $commandList = [];

        foreach ($this->commands as $namespace => $commands) {
            if (str_starts_with($namespace, $prefix)) {
                $commandList[$namespace] = $commands;
            } else {
                foreach ($commands as $name => $command) {
                    if (str_starts_with($name, $prefix)) {
                        $commandList[$namespace][$name] = $command;
                    }
                }
            }
        }

        return $commandList;
    }

    /**
     * 获取输入定义
     *
     * @return InputDefinition
     */
    public function getDefinition(): InputDefinition
    {
        return $this->definition ??= self::getDefaultDefinition();
    }

    /**
     * 获取输入定义
     *
     * @return InputDefinition
     */
    private static function getDefaultDefinition(): InputDefinition
    {
        return new InputDefinition(
            new Option('output', 'o', Option::OPTIONAL, 'Output mode: 0(decorative), 1(plain), 2(raw), 3(none)', '0')
        );
    }

    /**
     * 标准化执行码
     *
     * @param int $code
     * @param bool $error
     * @return int
     */
    private static function normalizeCode(int $code, bool $error = false): int
    {
        $min = $error ? 1 : 0;

        if ($code > 255) {
            $code = 255;
        } elseif ($code < $min) {
            $code = $min;
        }

        return $code;
    }

    /**
     * 运行时对抛出的错误或异常进行处理，返回错误码的标准化结果
     *
     * @param Throwable $throwable
     * @param Output|null $output
     * @return int
     */
    private static function throwable(Throwable $throwable, ?Output $output = null): int
    {
        $output?->throwable($throwable);
        return self::normalizeCode($throwable->getCode(), true);
    }

    /**
     * 确定输出模式
     *
     * @param Output $output
     * @param string $mode
     */
    private function determineOutputMode(Output $output, string $mode): void
    {
        $this->setOutMode($output, $mode);
    }

    /**
     * 设置输出模式
     *
     * @param Output $output
     * @param string|null $mode
     */
    private function setOutMode(Output $output, string $mode = null): void
    {
        switch ($mode) {
            case '1':
                $output->setMode(Output::OUTPUT_PLAIN);
                break;
            case '2':
                $output->setMode(Output::OUTPUT_RAW);
                break;
            case '3':
                $output->setMode(Output::OUTPUT_QUIET);
                break;
            case '0':
                // no break
            default:
                $output->setMode(Output::OUTPUT_DECORATE);
        }
    }

    /**
     * 输出控制台描述
     *
     * @param Output $output
     */
    private function describe(Output $output): void
    {
        $descriptor = new Descriptor($output);
        $descriptor->describeConsole($this);
    }
}
