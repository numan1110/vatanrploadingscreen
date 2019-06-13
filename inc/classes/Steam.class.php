<?php

use SteamID\SteamID;
use Ehesp\SteamLogin\SteamLogin;

class Steam {

	public static $host;
	public static $current;

	private static $apikey;


	public static function Init() {
		self::$host = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].chop($_SERVER['PHP_SELF'], '/index.php');
		self::$current = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	}

	public static function Key(string $key) {
		self::$apikey = $key;
	}

	public static function LoginUrl() {
		$steamauth = new SteamLogin();
		return $steamauth->url(self::$current);
	}

	public static function login() {
		self::Redirect(self::LoginUrl());
	}
	public static function Logout() {
		session_destroy();
		if (isset($_SERVER['HTTP_REFERER'])) {
			if (strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) !== false) {
				self::redirect($_SERVER['HTTP_REFERER']);
			}
		}
		self::redirect();
	}

	public static function Validate() {

		$steamauth = new SteamLogin();
		try {
			$steamid = $steamauth->validate();
			if (isset($steamid)) {
				self::session($steamid);
			}
		} catch (Exception $e) {
			self::logout();
		}
	}

	public static function Redirect($url = null, $force = false) {
		if (!$url) {
			$url = self::$host;
		}

		header('Location: '.$url, true, 302);
		die();
	}

	public static function Session($steamid) {
		$_SESSION =  self::Convert($steamid);
		$_SESSION['logged_in'] = 1;
		self::redirect($_GET['openid_return_to'] ?? null);
	}

	public static function Convert($steamid) {
		try {
			$steamids = new SteamID($steamid);
			$data['steamid'] = $steamids->getSteamID64();
			$data['steamid2'] = $steamids->getSteam2RenderedID();
			$data['steamid3'] = $steamids->getSteam3RenderedID();
			return $data;
		} catch (Exception $e) {
			return;
		}
	}

	public static function Info($steamids, $format = 'json') {

		$url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.self::$apikey.'&steamids='.$steamids.'&format='.$format;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, $url);
		$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode($result, true);

		return $result;
	}

	public static function User($steamid, $format = null) {
		$steamid = explode(',', $steamid);
		$steamid = $steamid[0];
		$user = self::Info($steamid, $format);
		return $user['response']['players'][0] ?? null;
	}

	public static function Users($steamids, $format = null) {
		if (is_array($steamids)) { $steamids = implode(',', $steamids); }
		if (is_string($steamids)) { $steamids = preg_replace('/\s+/', '', $steamids); }

		$users = self::Info($steamids, $format);
		return $users['response']['players'] ?? null;
	}

	public static function Group($name) {

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://steamcommunity.com/groups/'.$name.'/memberslistxml/?xml=1');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$data = simplexml_load_string(curl_exec($curl),'SimpleXMLElement',LIBXML_NOCDATA);
		curl_close($curl);

		return $data ?? null;
	}

}
