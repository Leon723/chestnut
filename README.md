# cheatnut
yound's php framework

### Install

Install with composer

    composer required "yound912/cheatnut:1.0.0"

Create index.php in your project with:

    <?php
    require_once "../vendor/autoload.php";

    $app = new Cheatnut\Core\Application();

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
