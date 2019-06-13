<?php

namespace K_Load;

use Database;
use Steam;

class Test {

	public static $steamid = 'YOURSTEAMIDHERE';

	public static function steam($key) {
		Steam::Key($key);
		$info = Steam::User(self::$steamid);
		return !empty($info['personaname']);
	}

	public static function mysql($config) {
		Database::connect([
			'host' => $config['host'],
			'user' => $config['user'],
			'pass' => $config['pass'],
			'db' => $config['db'],
			'port' => $config['port']
		]);
		return Database::ping();
	}

}
