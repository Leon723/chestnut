# Chestnut
Chestnut php framework

### Install

Install with composer

    composer required "leon723/chestnut:1.0.2"

Create index.php in your project with:

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

Use ViewTemplateEngine

    Route::get('/', function() {
      return View::make('index');
    });

or

    Route::get('/', function() {
      return View::make('index', ['hello'=>'hello'])->world('World');
    });
