<?php
namespace Chestnut\Component\Auth\Model;

use Model;
use Schema;

class Role extends Model {
	public function schema(Schema $table) {
		$table->increment('id');
		$table->string('role_name');
		$table->string('permission');
	}
}