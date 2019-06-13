<?php

namespace K_Load\Controller;

use Database;
use K_Load\Util;
use Steam;

class Test {

	public static $steamid = YOURSTEAMIDHERE;

	public static function steam() {
		$status = \K_Load\Test::steam($_POST['key']);
		Util::json(['success' => $status, 'message' => ($status ? 'Steam API Key works' : 'Please verify your key and try again')], true);
	}
	public static function mysql() {
		$status = \K_Load\Test::mysql($_POST['mysql']);
		Util::json(['success' => $status, 'message' => ($status ? 'MySQL connection established' : 'Please check the log in <code>data/logs/mysql</code>')], true);
	}

}
