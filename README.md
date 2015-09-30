# Chestnut
Chestnut PHP framework
## 安装

通过 [composer](http://www.phpcomposer.com/) 安装


```
composer required "leon723/chestnut:~0.5.0"
```

在项目根目录创建 index.php 并输入以下内容：

```
<?php
require '../vendor/autoload.php';

use Chestnut\Application\Application;

$app = new Application(
  realpath('../')
);

require '../app/route.php';

$app->boot();
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
