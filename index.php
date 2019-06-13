<?php

// debug time
$start = microtime(true);

// data/constants.php
include __DIR__.'/data/constants.php';

// fuck people and shit hosts
if (!defined('PHP_VERSION_ID')) {
	$version = explode('.', PHP_VERSION);
	define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
if (PHP_VERSION_ID < 70000) {
	echo "<h1>php 7 and up is required, see if your host can upgrade or get a better host<h1><br><hr><br>";
	phpinfo();
	die();
}

// everything else
require_once __DIR__.'/inc/init.php';

use K_Load\Util;

if (!Util::isAjax()) {
	echo '<!-- loaded in '.(microtime(true) - $start).' secs -->';
}
