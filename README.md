# cheatnut
yound's php framework

### Install

Install with composer

    composer required "yound912/cheatnut:dev-master"

Create index.php in your project with:

    <?php
    require_once "../vendor/autoload.php";

    $app = new Cheatnut\Cheatnut();
    
    Router::get('/', function() {
        echo "hello world";
    });
    
    Router::get('/:name', function($name) {
        echo "hello $name";
    });

    $app->run();
