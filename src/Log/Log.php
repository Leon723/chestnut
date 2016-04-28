<?php
namespace Chestnut\Log;

use Auth;

class Log {
	public static function write($modifies, $operation = 'insert') {
		if (empty($modifies)) {
			return false;
		}

		$log_content = [];
		$user_id = Auth::getId();
		$module = app('current')->getIdentifier();

		foreach ($modifies as $key => $value) {
			array_push($log_content, [config("lang.{$key}", $key) => $value]);
		}

		$log_content = json_encode($log_content);

		Model\Log::create(compact('user_id', 'module', 'log_content', 'operation'));
	}
}
