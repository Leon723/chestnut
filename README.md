# Chestnut
Chestnut PHP framework

## 安装

通过 [composer](http://www.phpcomposer.com/) 安装


```
composer required "leon723/chestnut:~0.2.0"
```

在项目根目录创建 index.php 并输入以下内容：

```
<?php
require_once "../vendor/autoload.php";

$app = new Chestnut\Core\Application();

Route::get('/', function() {
  echo "hello world";
});

Route::get('/:name', function($name) {
  echo "hello $name";
});

$app->run();
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
