<?php
namespace Chestnut\Database;

use Chestnut\Database\Nut\Model;
use Chestnut\Database\Query\Query;
use Chestnut\Support\Register;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class DatabaseRegister extends Register {
	public function register() {
		$this->registerDb();
		$this->registerQuery();

		Model::setEvent($this->app->event);
		Model::setContainer($this->app);
	}

	public function registerDb() {
		$this->app->register([Factory::class => 'db'], function ($app, $driver = null) {
			return (new Factory($app))->driver($driver);
		});

		$this->app->registerAlias("DB", DatabaseStatic::class);
	}

	public function registerQuery() {
		$this->app->register([Query::class => 'db.query'], function () {
			$db = $this->app->make('db');
			return new Query($db->newQuery(), $db->getDriver());
		});

		$this->app->registerAlias('Query', QueryStatic::class);
	}
}