<?php

$settings = [
	['version', '0.0.1'],
	['gms_ver', '19180'],
	['community_name', 'K-Load'],
	['backgrounds', '[]'],
	['description', 'Sample description'],
	['messages', '[]'],
	['rules', '[]'],
	['staff', '[]'],
	['youtube', '[]'],
	['test', 'YOURSTEAMIDHERE']
];

Database::conn()->insert("INSERT INTO `kload_settings` (`name`, `value`)")->values($settings)->execute();

?>
