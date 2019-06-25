# strack_log

#### 项目介绍
Strack独立记录event log服务器

```
php start_swoole_server.php list
Console Tool

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  clear    Clear server actor and timer callback
  help     Displays help for a command
  kill     Kill server
  list     Lists commands
  reload   Reload server
  restart  Restart server
  start    Start server
  status   Server Status
  stop     Stop(Kill) server
  test     Test case
```

#### 启动

调试模式启动服务器

```
php start_swoole_server.php start
```

#### 守护进程启动服务器

```
php start_swoole_server.php start -d
```

#### 调试
```
php start_swoole_server.php start --debug
```

#### 还可以附加过滤器
```
php start_swoole_server.php start --debug --f abc
```

#### 重启

会自动结束进程然后重新启动一个守护进程模式的服务器
```
php start_swoole_server.php restart
```

#### 重载

不会断开客户端链接，进行代码的重载。升级服务器逻辑，客户端无感知。
```
php start_swoole_server.php reload
```

#### 停止

停止服务器
```
php start_swoole_server.php stop
```

#### 强杀

有时候会出现stop失败的情况，这时候可以使用kill命令强杀。
```
php start_swoole_server.php kill
```

#### 单元测试
测试test目录下所有的测试类
```
php start_swoole_server.php test
```

#### 测试test目录下指定的测试类
```
php start_swoole_server.php test XXXX
```

#### 远程断点调试
```
php start_swoole_server.php -xdebug
```

#### 代码覆盖率收集
```
php start_swoole_server.php -coverage
```