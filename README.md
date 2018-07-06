# SwooleGlue
server端集成swoole的插件，方便集成到现有的框架中，实现项目的无缝迁移，新一代LAMP开发模式




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
