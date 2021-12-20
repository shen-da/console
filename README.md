## 控制台组件

该组件可以创建命令行命令，用于任何反复（执行）的任务，如定时任务，或其他批处理工作。

### 运行环境

- PHP >= 8.0

### 安装

```
composer require loner/console
```

### 快速入门

* 脚本文件（index.php）

  ```php
  #!/usr/bin/env php
  <?php
  
  use App\Console\DemoCommand;
  use Loner\Console\Console;
  use Loner\Console\Input\Input;
  use Loner\Console\Output\{Output, Formatter};
  
  // 引入 composer 自加载文件路径（根据实际路径修改）
  require __DIR__ . '/vendor/autoload.php';
  
  // 控制台实例
  $console = new Console();
  
  // 添加用户自定义命令对象
  if (class_exists(DemoCommand::class)) {
      $console->add(new DemoCommand());
  }
  
  // 输入对象
  $input = new Input();
  
  // 输出格式
  $formatter = new Formatter();
 
  // 输出对象
  $output = new Output($formatter);
  
  // 运行控制台实例
  $code = $console->run($input, $output); # 0 ~ 255
  
  exit($code);
  ```
* 查看控制台信息
  ```shell
  > php index.php
  ```

* 脚本使用说明
  > 用法（命令行输入回车）：
  > 1. php index.php [command [arguments]] [options]
  > 2. php index.php [command [arguments]] [options] [-- raw_arguments]
  >
  > 参数说明：
  > * command &emsp;&emsp;&emsp;&nbsp; 调用的命令名称
  > * argument &emsp;&emsp;&emsp;&nbsp;&nbsp; 命令的常规参数（按位置）值，不能以”-“开头，以免和选项冲突
  > * option &emsp;&emsp;&emsp;&emsp;&emsp; 选项（--名称、-快捷方式）及值（不能以”-“开头）
  > * raw_argument &emsp;&nbsp; 同 argument，但可以”-“开头

* 消息输出设置

  > 控制台选项，对所有命令有效，名称为”output“，快捷方式为”o“
  >
  > 取值（默认 0）：0（装饰标签）、1（去除标签）、2（原样输出）、3（不输出）
  >
  > 使用：
  > * --output=值
  > * --output 值
  > * -o 值

  示例：
  ```shell
  > php index.php -O 1
  ```

* 列出命令：list

  > 控制台默认命令，用于列出名称或命名空间具有指定前缀的命令

  示例：
  ```shell
  # 列出全部命令
  > php index.php list
    
  # 列出名称或命名空间以“demo”开头的命令
  > php index.php list demo
  ```

* 查看命令：help

  > 控制台默认命令，用于查看指定命令的详情

  示例：
  ```shell
  # 查看 help 命令自身详情
  > php index.php help
    
  # 查看 demo 命令详情
  > php index.php help demo
  ```

* 自定义命令（DemoCommand）

    ```php
    <?php
    
    declare(strict_types=1);
    
    namespace App\Console;
    
    use Loner\Console\Command\Command;
    use Loner\Console\Input\{Definition\Argument, Definition\Option, Input};
    use Loner\Console\Output\Output;
    
    class DemoCommand extends Command
    {
      /**
       * 返回命令默认名称
       *
       * @return string
       */
      public static function getDefaultName(): string
      {
          return 'demo';
      }
    
      /**
       * 返回命令默认描述
       *
       * @return string
       */
      public static function getDefaultDescription(): string
      {
          return 'This is a demo command';
      }
    
      /**
       * 返回命令输入定义参数组
       *
       * @return <Argument|Option>[]
       */
      public function getDefinitions(): array
      {
          return [];
      }
    
      /**
       * 运行命令
       *
       * @param Input $input
       * @param Output $output
       * @return int
       */
      public function run(Input $input, Output $output): int
      {
          return 0;
      }
    }
    ```

* 自定义命令 - 输入定义参数组

  > 定义参数组为输入定义参数索引数组，输入定义参数分为：Argument（位置参数）、Option（选项）
  >
  > 其中 Argument 之间严格按照顺序，Option 之间不作顺序要求

    * 位置参数（Argument）

      ```php
      public function __construct(string $name, int $mode = 0, string $description = '', string ...$defaults){}
      ```

      说明：
      > * name 参数名称
      >   * 最好复合变量命名规范

      > * mode 取值模式
      >   * 0
      >     * 存在默认值，可选择是否提供值（此类参数后不能接含 Argument::REQUIRED 参数）
      >   * Argument::REQUIRED
      >     * 必须提供值
      >   * Argument::COMPLEX
      >     * 复合（多）值（此类参数只能放在末尾）
      >   * Argument::COMPLEX | Argument::REQUIRED
      >     * 复合（多）值，必须提供值（此类参数只能放在末尾）

      > * description 简介

      > * defaults 默认值列表

      注意：
      >   * 含 REQUIRED 模式，不能设置默认值
      >   * 只有含 COMPLEX 模式，默认值才能设置多个
      >   * 参数值不建议以”-“开头

    * 选项（Option）

      ```php
      public function __construct(string $name, ?string $shortcut = null, int $mode = 0, string $description = '', string ...$defaults){}
      ```

      说明：
      > * name 参数名称
      >  * 不能为空字符串，不能含空字符

      > * shortcut 快捷方式
      >  * 单字符，不能为空字符，不能为“-”

      > * mode 取值模式
      >   * 0
      >     * 不取值，只用于判断是否含该选项
      >   * Option::REQUIRED
      >     * 必须提供值 
      >   * Option::OPTIONAL
      >     * 可选择是否提供值
      >   * Option::COMPLEX | Option::REQUIRED
      >     * 复合（多）值、必须提供值
      >   * Option::COMPLEX | Option::OPTIONAL
      >     * 复合（多）值、可选择是否提供值

      > * description 简介

      > * defaults 默认值列表

      注意：
      >   * 含 REQUIRED 模式，不能设置默认值
      >   * 只有含 COMPLEX 模式，默认值才能设置多个
      >   * 含 OPTIONAL 模式，必须设置默认值；反之，必不能设置默认值

* 自定义命令 - 运行命令函数

  ```php
  public function run(Input $input, Output $output): int{}
  ```
  简单使用：

  > * 输入实体
  >   ```php
  >   # 获取输入实体（自动检测提供参数是否复合参数模式，自动检测多余位置参数和选项，异常则报错）
  >   $inputConcrete = $this->getConcrete($input);
  >   ```

  > * 位置参数
  >
  >   ```php
  >   # 获取指定位置（从0开始计算）参数值
  >    # 复合型，返回字符串值列表；否则返回字符串
  >    # 若指定位置未提供参数及默认值，则返回 null
  >   $inputConcrete->getArgument(位置整型);
  >   ```

  > * 选项
  >
  >   ```php
  >   # 返回是否有选项
  >   $inputConcrete->hasOption('选项名');
  >
  >   # 获取选项值
  >    # 复合型，返回字符串值列表；否则返回字符串
  >    # 若指定选项名，为提供值，返回 null
  >   $inputConcrete->getOption('选项名');    
  >   ```

  > * 消息输出
  >
  >   ```php
  >   $output->write('这是普通消息');
  >   $output->write(PHP_EOL);
  >   
  >   # 第二个参数表示是否换行，第三个参数表示输出模式，优先于控制台选项output
  >   $output->write('这里的<info>信息标签</info>不会被解析', false, Output::OUTPUT_RAW);
  >   $output->write(PHP_EOL);
  >   
  >   $output->write('这是信息标签样式：<info>信息</info>');
  >   $output->write(PHP_EOL);
  >   
  >   $output->write('这是注释标签样式：<comment>注释</comment>');
  >   $output->write(PHP_EOL);
  >   
  >   $output->write('这是错误标签样式：<error>错误</error>');
  >   $output->write(PHP_EOL);
  >   
  >   $output->write('<css href="http://baidu.com">自定义标签，链接到百度</css>');
  >
  >   // Loner\Console\Output\Style
  >   $output->write(sprintf(
  >      '<css fg="%s" bg="%s" options="%s">自定义标签，附带属性：%s</css>',
  >      Style::RED, Style::GREEN, join(',', [Style::HIGHLIGHT, Style::UNDERLINE]),
  >      '红字，绿底，高亮、下划线'
  >   ));
  >   $output->write(PHP_EOL);
  >   
  >   $output->writeln('在 write 的基础上换行');
  >   # 第二个参数表示输出模式，优先于控制台选项output
  >   $output->writeln('这里的<info>标签</info>将被无视', Output::plain);

  向用户提问

  > * 创建提问对象
  >
  >   ```php
  >   # Loner\Console\Helper\Questioner
  >   $questioner = new Questioner($output);
  >   ```

  > * 常规问题
  >
  >   ```php
  >   # 创建普通问题
  >   # Loner\Console\Question\Question
  >   # 参数：问题、默认回答（默认为 null）
  >   $question = new Question('请输入你的银行卡密码？', '123456');
  >   
  >   # 隐藏用户响应
  >   $question->setHidden(true); // 默认 false，不隐藏
  >   # 隐藏失败时，不可以回退为非隐藏问题
  >   $question->setHiddenFallback(false);  // 默认 true，隐藏失败，可以作不隐藏处理
  >   
  >   # 对用户响应个体不去除两边空白字符
  >   $question->setTrim(false); // 默认 true, 去除两边空白字符
  >   
  >   # 设置用户响应格式器
  >   $question->setNormalizer(fn($answer) => (string)$answer); // 默认 null，无格式器
  >   
  >   # 设置用户响应验证器（默认 null，无验证器）
  >   $question->setValidator(function($answer) {
  >       if (strlen($answer) !== 6) {
  >           # Loner\Console\Exception\QuestionValidationException
  >           throw new QuestionValidationException('密码必须是6位数');
  >       }
  >       return $answer;
  >   });
  >   # 验证尝试次数（默认 null，不限次数）
  >   $question->setMaxAttempts(3);
  >
  >   # 获取询问结果
  >   $answer = $questioner->ask($question, $input);
  >   ```

  > * 确认性问题
  >
  >   ```php
  >   # 创建普通问题
  >   # Loner\Console\Question\ConfirmationQuestion
  >   # 参数：问题、默认回答（默认为 true）
  >   $question = new ConfirmationQuestion('你吃过饭了吗？', false);
  >   
  >   # 说明：用户输入“y”或“yes”后回车，或者直接回车，都是 true；否则为 false
  >
  >   # 获取询问结果
  >   $answer = $questioner->ask($question, $input);
  >   ```

  > * 选择性问题
  >
  >   ```php
  >   # 创建普通问题
  >   # Loner\Console\Question\ChoiceQuestion
  >   # 参数：问题、选项（关联或索引数组）、默认回答（字符串/整数，即选项下标或下标拼接字符串；默认为 null，自动识别为首个下标）
  >   $question = new ChoiceQuestion('你喜欢什么颜色？', ['黑色', '白色', 'green' => '绿色'], '0,green');
  >   
  >   # 设置多选
  >   $question->setMultiselect(true);  // 默认 false，单选
  >   
  >   # 获取询问结果（单选返回选项下标，多选返回下标列表）
  >   $answer = $questioner->ask($question, $input);
  >   ```
