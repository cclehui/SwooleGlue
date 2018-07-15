# SwooleGlue
server端集成swoole的插件，方便集成到现有的框架中，实现项目的无缝迁移，新一代LAMP开发模式

```php
//安装配置
php bin/SwooleGlue.php install 

实现SwooleGlue\Component\PServerlet类，在这里做业务处理，并在配置文件中配置实现后的 PServerlet类


启动server
php bin/SwooleGlue.php start 

```

# http response header问题解决
fpm模式下通常通过header(), setcookie()等函数来设置response 的header信息
，由于咱们使用cli模式运行php， 常规的header_list()取不到header信息了， 需要对header(),
setcookie()进行封装替换


# nginx + SwooleGlue  配置
```
server {
    listen  80;
    server_name  _;
    root  /var/www/test;

    location / {
        if (!-e $request_filename){
            proxy_pass http://127.0.0.1:9501;
        }
    }


```


## fastcgi 协议解析
fastcgi协议解析引入了 lisachenko/protocol-fcgi 1.1.1 版本，
参考https://github.com/lisachenko/protocol-fcgi/tree/1.1.1