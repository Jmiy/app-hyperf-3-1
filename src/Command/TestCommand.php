<?php

declare(strict_types=1);

namespace Business\Hyperf\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
#[Command]
class TestCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('test:command');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('test:command Demo Command');
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
            ['foo', 'F', InputOption::VALUE_OPTIONAL, '测试在 Command 中运行其他命令', 'foo'],
        ];
    }

    public function handle()
    {
        var_dump('Hello test:command!===foo===>'.$this->input->getOption('foo'));
//        $this->line('Hello test:command!===foo===>'.$this->input->getOption('foo'), 'info');
    }
}
