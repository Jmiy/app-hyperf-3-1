<?php

use Symfony\Component\Console\Output\ConsoleOutput;
use Swoole\Process;
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    include $autoload;

    if (class_exists('\Symfony\Component\Console\Output\ConsoleOutput')) {
        $logger = new ConsoleOutput();
    }
}

if (!isset($logger)) {
    $logger = new class() {
        public function write(string $msg)
        {
            file_put_contents('php://stderr', $msg);
        }

        public function writeln(string $msg)
        {
            $this->write($msg . PHP_EOL);
        }
    };
}

if (!extension_loaded('swoole') && !dl('swoole.so')) {
    $logger->writeln("swoole extension isn't loaded");
    exit(255);
}

$usage = function() use ($logger) {
    global $argv;
    $msg = <<<USAGE
Usage: {$argv[0]} OPTIONS

  -c <path>     Hyperf 启动脚本路径
  -f <path>     Hyperf PID 文件路径
  -p <pid>      当前的 Master PID
  -a <token>    钉钉机器人 access token
  -s <secret>   <optional>钉钉机器人 加密密钥
  -P <port>     <optional>Hyperf 服务端口，多个端口的话，填写任意端口即可，默认 <9501>
  -t <seconds>  <optional>等待服务结束超时时间，单位 秒，可选值 3-60，默认 <60>
  -m <message>  <optional>钉钉消息，默认只支持text消息，其他类型消息，需通过 base64 格式提交
                text 消息只需提交 text.content，复杂内容可通过 base64 格式提交，默认 atAll

USAGE;
    $logger->writeln($msg);
    exit;
};

/**
 * 根据 PID 检测是否僵尸进程
 * 当前仅支持 Linux 系统
 *
 * @param int $pid
 * @return bool
 * @todo 支持 Unix 系统，Windows 等 swoole 支持了再说
 */
$isZombieProcess = function(int $pid):bool {
    if (false !== ($stat = @file_get_contents("/proc/{$pid}/stat"))) {
        list(,, $status) = explode(' ', $stat, 4);
        // 进程状态为 Z，进程已成为僵尸进程
        return 'Z' === $status;
    }

    return false;
};

$options = getopt('c:f:p:a:t:m:P:s:');
if (!isset($options['p'], $options['a'], $options['c'], $options['f'])) {
    $usage();
}

$token = trim($options['a']);
if (1 !== preg_match('/^[0-9a-f]{64}$/', $token)) {
    $logger->writeln('Invalid dingtalk access token');
    exit(1);
}

$secret = trim($options['s'] ?? '');
if ($secret !== '' && 1 !== preg_match('/^SEC[0-9a-f]{64}$/', $secret)) {
    $logger->writeln('Invalid dingtalk api secret');
    exit(1);
}

$pid = (int)$options['p'];
if ($pid <= 0) {
    $logger->writeln("Invalid master pid: {$pid}");
    exit(1);
}

$pidFile = trim($options['f']);
if (!is_readable($pidFile)) {
    $path = $pidFile;
    while (!is_readable($path)) {
        $path = dirname($path);
        if ('.' === $path || '/' === $path) {
            $path = '';
            break;
        }
    }

    if ('' === $path) {
        $logger->writeln("{$pidFile}: No such file or directory");
        exit(1);
    }
}

$command = trim($options['c']);
if (!is_readable($command)) {
    if (file_exists($command)) {
        $logger->writeln("permission denied: {$command}");
    } else {
        $logger->writeln("{$command}: No such file or directory");
    }
    exit(1);
}
$command = realpath($command);
$port = min(65535, max(1, intval($options['P'] ?? 9501)));
$timeout = min(60, max(3, intval($options['t'] ?? 60)));
$message = trim($options['m'] ?? '') ?: 'hello dingding';
if (false !== ($b64msg = @base64_decode($message, true))) {
    $message = $b64msg;
}

$first = true;
$startFail = false;
$isZombie = false;
$zombieMsg = '<fg=red>Master process zombied</>';

// 开始之前检测是否已成为僵尸进程
if ($isZombieProcess($pid)) {
    $logger->writeln($zombieMsg);
    Process::kill($pid, \SIGTERM);
    $isZombie = true;
}

while ($timeout--) {
    if (!$isZombie && Process::kill($pid, 0)) {
        Process::kill($pid, \SIGTERM);
        $logger->write($first ? 'Stop service.' : '.');
        $first = false;
        sleep(1);
        // 倒计时最后一秒时检查进程是否已成为僵尸进程，是的话认定为退出成功
        if ($timeout === 1 && $isZombieProcess($pid)) {
            $logger->writeln('');
            $logger->writeln($zombieMsg);
            $isZombie = true;
        }
    } else {
        $logger->writeln('Start service...');
        (new Process(function(Process $proc) use ($command) {
            $proc->exec('/bin/sh', ['-c', PHP_BINARY . " {$command} start > /dev/stdout &"]);
        }))->start();

        // 30 秒内未启动的话，认定为启动失败
        $timeout = 30;
        while ($timeout--) {
            $newPID = intval(@file_get_contents($pidFile));
            if ($newPID > 0 && $newPID !== $pid && Process::kill($newPID, 0)) {
                $fp = @fsockopen('localhost', $port);
                if (is_resource($fp)) {
                    fclose($fp);
                    exit(0);
                }
            }

            sleep(1);
        }
        $startFail = true;
        break;
    }
}

$logger->writeln('');
$logger->writeln('<fg=red>' . ($startFail ? 'Start' : 'Stop') . ' service failed</>');

if (null !== ($json = @json_decode($message, true)) && isset($json['msgtype'])) {
    $data = $message;
} else {
    $data = json_encode([
        'msgtype' => 'text',
        'text' => ['content' => $message],
    ]);
}

$uri = "/robot/send?access_token=$token";
if ($secret) {
    $timestamp = time() * 1000;
    $sign = urlencode(base64_encode(hash_hmac('sha256', "{$timestamp}\n{$secret}", $secret, true)));
    $uri .= "&timestamp={$timestamp}&sign={$sign}";
}

$host = 'oapi.dingtalk.com';
$len = strlen($data);
$fp = fsockopen("ssl://{$host}", 443, $errno, $errstr, 30);
if (false === $fp) {
    $logger->writeln("Send warning message failed: {$errstr}");
    exit(2);
}

$result = fwrite(
    $fp,
    "POST {$uri} HTTP/1.1\r\nHost: {$host}\r\nContent-Type: application/json;charset=utf-8\r\nContent-Length: {$len}\r\nConnection: Close\r\n\r\n{$data}\r\n\r\n"
);

$code = 0;
if (false !== $result) {
    $resp = '';
    while (!feof($fp)) {
        // 这里没错，只取最后一行
        $resp = fgets($fp);
    }

    if ($resp) {
        if (null === ($json = @json_decode($resp, true))
            || !isset($json['errcode'], $json['errmsg'])
            || 0 !== $json['errcode'] || 'ok' !== $json['errmsg']
        ) {
            $code = 2;
            $logger->writeln("Send warning message failed: {$resp}");
        }
    } else {
        $code = 2;
        $logger->writeln("Send warning message failed: empty reply");
    }
} else {
    $code = 2;
    $logger->writeln("Send warning message failed: request failed");
}

fclose($fp);
exit($code);
