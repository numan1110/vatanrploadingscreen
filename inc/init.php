<?php

// show errors
if (DEBUG) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

// important constants
define('APP_ROOT', dirname(__FILE__,2));
define('APP_HOST', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['SERVER_NAME']);
define('APP_PATH', str_replace('/index.php', '', $_SERVER['PHP_SELF']));
define('APP_URL', APP_HOST.APP_PATH);
define('APP_URL_CURRENT', APP_HOST.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($_SERVER['REQUEST_URI'] == APP_PATH.'/?phpinfo') {
	phpinfo();
	die();
}

// sessions
if (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false || strpos($_SERVER['REQUEST_URI'], '/install') !== false) {
	session_name('K-Load');
	/*
		sessions on actual loading screen increase load times, dumb fix
		Yes, Billy would eat a muddy lizard for a million dollars.
	*/
	if (strpos($_SERVER['REQUEST_URI'], '/install') !== false) {
		session_set_cookie_params(0, APP_PATH.'/install', $_SERVER['SERVER_NAME'], isset($_SERVER["HTTPS"]), true);
	} else {
		session_set_cookie_params(0, APP_PATH.'/dashboard', $_SERVER['SERVER_NAME'], isset($_SERVER["HTTPS"]), true);
	}
	session_start();
}

// autoloading
function autoload_classes($class_name) {
	$class_name = (strpos($class_name, '\\') !== false ? str_replace('\\', DIRECTORY_SEPARATOR, $class_name).'.php' : $class_name.'.class.php');

	$file = __DIR__.'/classes/'.$class_name;
	$file_mod = __DIR__.'/classes/mods/'.$class_name;

	if (file_exists($file_mod) || file_exists($file)) {
		$load_file = $file;
		if (file_exists($file_mod)) {
			$load_file = (!file_exists($file) ? $file_mod : $file);
			if (file_exists($file)) {
				$load_file = filemtime($file) < filemtime($file_mod) ? $file_mod : $file;
			}
		}
		require_once $load_file;
	}
}
spl_autoload_register('autoload_classes');
require_once APP_ROOT.'/vendor/autoload.php';

// make it easier to call classes
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;

use K_Load\Routes;
use K_Load\Template;
use K_Load\Util;
use K_Load\User;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

// exception handler
function kload_exception_handler($exception) {
	global $start;

	$cloner = new VarCloner();
	$dumper = new HtmlDumper();

	$code = $exception->getCode();
	$message = $exception->getMessage();
	$trace = $exception->getTrace();

	Util::log('exception', 'Code: '.$code.' - '.$message, true);
	Util::log('exception', "\t\t".'Stack Trace: ', true);
	foreach ($trace as $file => $info) {
		Util::log('exception', "\t\t\t[".($file ?? 'N/A').'] - '.$info['function'].' '.($info['file'] ?? '<unknown file>').' on line '.($info['line'] ?? '<unknown>'), true);
	}

	$twig_loader = new Twig_Loader_Filesystem(APP_ROOT.'/themes/default/pages');

	$twig_env_params = [
		'auto_reload' => true,
		'debug' => true
	];
	$twig = new Twig_Environment($twig_loader, $twig_env_params);
	$twig->addExtension(new Twig_Extension_Debug());

	$data = [
		'assets' => APP_PATH.'/assets',
		'error' => [
			'code' => $code,
			'message' => $message,
			'trace' => DEBUG ? $dumper->dump($cloner->cloneVar($trace), true) : ''
		]
	];
	$data['time'] = (microtime(true) - $start);

	$twig->load('error.twig')->display($data);
	die();

}
set_exception_handler('kload_exception_handler');

/*
error handler - todo, doesn't affect usage

function kload_error_handler($errno, $errstr, $errfile, $errline) {
	if (!(error_reporting() & $errno)) {
		return false;
	}
	switch ($errno) {
		case E_USER_ERROR:
			# code...
			break;
		case E_USER_WARNING:
			# code...
			break;
		case E_USER_NOTICE:
			# code...
			break;
		default:
			# code...
			break;
	}
}

*/

// steam auth and api stuff
Steam::Init();
if (isset($_GET['openid_claimed_id'])) {
	Steam::validate($_GET['openid_return_to']);
}

// logging
if (ENABLE_LOG) {
	Util::log();
}

// caching
if (!ENABLE_CACHE || isset($_GET[CLEAR_CACHE])) {
	if (file_exists(APP_ROOT.'/data/cache')) {
		Util::rmDir(APP_ROOT.'/data/cache');
	}
}
$cache = new J0sh0nat0r\SimpleCache\Cache(J0sh0nat0r\SimpleCache\Drivers\File::class, ['dir'=>APP_ROOT.'/data/cache']);
Cache::bind($cache);

// get config if exists
if (file_exists(APP_ROOT.'/data/config.php')) {
	$config = include APP_ROOT.'/data/config.php';
	Steam::Key($config['apikeys']['steam']);
	Database::connect($config['mysql']);
}

// check if installed
if (!Util::installed() &&  strpos($_SERVER['REQUEST_URI'], '/test/') === false) {
	if (strpos($_SERVER['REQUEST_URI'], '/install') === false) {
		if (isset($_SESSION['id'])) {
			session_destroy();
		}
		Util::redirect('/install');
	}
	include __DIR__.'/install.php';
	die();
}

if (isset($_SESSION['steamid']) && Util::installed()) {
	if (User::isSuper($_SESSION['steamid']) || ENABLE_REGISTRATION) {
		if (!isset($_SESSION['id'])) {
			User::add($_SESSION['steamid']);
			User::session($_SESSION['steamid']);
		}
	} else {
		echo "Registration is not allowed.";
		die();
	}
	if (strpos($_SERVER['REQUEST_URI'], 'api') === false) {
		if (User::isBanned($_SESSION['steamid'])) {
			echo "You're banned";
			die();
		}
	}
	$_SESSION['perms'] = array_fill_keys(array_keys(array_flip(json_decode(User::getInfo($_SESSION['steamid'], 'perms'), true))), 1);
}

// template
Template::init();

// routing
$routes = (ENABLE_CACHE ? Cache::remember('routes', 86400, [Routes::class, 'get']) : Routes::get());
$dispatcher = new Phroute\Phroute\Dispatcher($routes);
$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
