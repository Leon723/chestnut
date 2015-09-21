# Chestnut
Chestnut PHP framework
框架重构中，请使用 0.4.0 版本的框架

## 安装

通过 [composer](http://www.phpcomposer.com/) 安装


```
composer required "leon723/chestnut:~0.5.0"
```

在项目根目录创建 index.php 并输入以下内容：

```
<?php
require_once "../vendor/autoload.php";

/**
 * 创建 Chestnut 实例
 */
$app = new Chestnut\Application(realpath("../"));

Route::get('/', function() {
  echo "hello world";
});

Route::get('/:name', function($name) {
  echo "hello $name";
});

/**
 * 启动程序
 */
$app->boot();

/**
 * 运行程序
 */
$app->terminate();
```

## 美化链接

### Apache

```
Options +FollowSymLinks
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

### Nginx

```
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```
