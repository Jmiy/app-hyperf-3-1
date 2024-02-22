<?php

declare(strict_types=1);

namespace Business\Hyperf\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
#[Command]
class FooCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('demo:command');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
        $this->setHelp('Hyperf 自定义命令演示');
//        $this->addUsage('--name 演示代码');

        /**
         * 设置参数
         * 模式	                    值	备注
         * InputArgument::REQUIRED	1	参数必填，此种模式 default 字段无效
         * InputArgument::OPTIONAL	2	参数可选，常配合 default 使用
         * InputArgument::IS_ARRAY	4	数组类型
         */
//        $this->addArgument('name9', InputArgument::OPTIONAL, '姓名', 'Hyperf');

        /**
         * 设置选项
         * 选项支持以下模式。
         * 模式	                    值	备注
         * InputOption::VALUE_NONE	1	是否传入可选项 default 字段无效
         * InputOption::VALUE_REQUIRED	2	选项必填
         * InputOption::VALUE_OPTIONAL	4	选项可选
         * InputOption::VALUE_IS_ARRAY	8	选项数组
         */
//        $this->addOption('opt', 'o', InputOption::VALUE_NONE, '是否优化');
    }

    protected function getArguments()
    {
        /**
         * 设置参数
         * 模式	                    值	备注
         * InputArgument::REQUIRED	1	参数必填，此种模式 default 字段无效
         * InputArgument::OPTIONAL	2	参数可选，常配合 default 使用
         * InputArgument::IS_ARRAY	4	数组类型
         * 如： php bin/hyperf.php demo:command 55 66 88 999 22
         */
        return [
            ['name', InputArgument::OPTIONAL, '这里是对这个参数的解释'],
            ['name9', InputArgument::OPTIONAL, '姓名','Hyperf'],
            ['array', InputArgument::IS_ARRAY, '数组',[]],
        ];
    }

    protected function getOptions()
    {
        /**
         * 设置选项
         * 选项支持以下模式。
         * 模式	                    值	备注
         * InputOption::VALUE_NONE	1	是否传入可选项 default 字段无效
         * InputOption::VALUE_REQUIRED	2	选项必填
         * InputOption::VALUE_OPTIONAL	4	选项可选
         * InputOption::VALUE_IS_ARRAY	8	选项数组
         * 如： php bin/hyperf.php demo:command -o -N=姓名 -A=数组 -A=数组1
         */
        return [
            ['opt', 'o', InputOption::VALUE_NONE, '是否优化'],
            ['name', 'N', InputOption::VALUE_REQUIRED, '姓名', 'Hyperf'],
            ['array', 'A', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, '数组', []],
        ];
    }

    public function handle()
    {
        //帮助  php bin/hyperf.php demo:command --help
        // 从 $input 获取 name 参数
        $argument = $this->input->getArgument('name') ?? 'World';

        //获取所有参数
//        array(4) {
//            ["command"]=>
//            string(12) "demo:command"
//            ["name"]=>
//            string(2) "55"
//            ["name9"]=>
//            string(2) "66"
//            ["array"]=>
//            array(3) {
//                [0]=>
//                string(2) "88"
//                [1]=>
//                string(3) "999"
//                [2]=>
//                string(2) "22"
//              }
//        }
        $arguments = $this->input->getArguments();//获取所有参数
        var_dump($arguments);
        $this->line('Hello ' . $argument, 'info');

        //php bin/hyperf.php demo:command -o -N=姓名 -A=数组 -A=数组1 --enable-event-dispatcher
//        array(10) {
//            ["opt"]=>
//            bool(true)
//            ["name"]=>
//            string(7) "=姓名"
//            ["array"]=>
//            array(2) {
//                [0]=>
//                string(7) "=数组"
//                [1]=>
//                string(8) "=数组1"
//              }
//              ["enable-event-dispatcher"]=> bool(true)
//              ["help"]=>
//              bool(false)
//              ["quiet"]=>
//              bool(false)
//              ["verbose"]=>
//              bool(false)
//              ["version"]=>
//              bool(false)
//              ["ansi"]=>
//              NULL
//              ["no-interaction"]=>
//              bool(false)
//        }
        var_dump($this->input->getOptions());
        var_dump($this->input->getOption('opt'));

//        //在 Command 中运行其他命令
//        $this->call('test:command', [
//            '--foo' => 'foo'
//        ]);
    }

}
