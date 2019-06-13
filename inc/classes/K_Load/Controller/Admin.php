<?php

namespace K_Load\Controller;

use Database;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Setup;
use K_Load\Template;
use K_Load\Util;
use K_Load\User;

class Admin {

	public static function index() {

		$data = [];
		$data['updates'] = Setup::getUpdates();

		if (isset($_SESSION['steamid'])) {
			if (User::isSuper($_SESSION['steamid']) && isset($_GET['action'])) {
				switch ($_GET['action']) {
					case 'update':
						if ($data['updates']['amount'] > 0) {
							Setup::update();
							$data['alert'] = 'An update was attempted, please make sure everything is working and check the logs';
						} else {
							$data['alert'] = 'There are no updates';
						}
						break;
					case 'clear_cache':
						Util::rmDir(APP_ROOT.'/data/cache');
						Util::log('action', 'Attempted to clear all cache');
						$data['alert'] = !file_exists(APP_ROOT.'/data/cache') ? 'All cache has been deleted' : 'Failed to clear all cache';
						break;
					case 'clear_cache_data':
						Util::log('action', 'Attempted to clear cached data');
						$data['alert'] = Cache::clear() ? 'Cached data has been cleared' : 'Failed to delete cached data';
						break;
					case 'clear_cache_template':
						Util::rmDir(APP_ROOT.'/data/cache/templates');
						Util::log('action', 'Attempted to clear template cache');
						$data['alert'] = !file_exists(APP_ROOT.'/data/cache/templates') ? 'Template cache has been deleted' : 'Failed to clear template cache';
						break;
					case 'refresh_css':
						$css_fixed = true;
						$files = glob(APP_ROOT.'/data/users/*');
						foreach ($files as $file) {
							unlink($file);
						}
						$user_count = Database::conn()->count("kload_users")->execute();
						$batches = ceil($user_count/5);
						for ($i = 0; $i < $batches; $i++) {
							$users = Database::conn()->select("SELECT `steamid`,`custom_css` FROM `kload_users`")->where("`custom_css` != NULL OR `custom_css` != ''")->limit(5, $i*5)->execute();
							foreach ($users as $user) {
								if (!empty($user['custom_css'])) {
									file_put_contents(APP_ROOT.'/data/users/'.$user['steamid'].'.css', Util::minify($user['custom_css']));
									if (!file_exists(APP_ROOT.'/data/users/'.$user['steamid'].'.css')) {
										Util::log('action', 'Failed to recreate CSS file for User: '.$user['steamid']);
										$css_fixed = false;
									} else {
										Util::log('action', 'Recreated CSS file for User: '.$user['steamid']);
									}
								}
							}
						}
						$data['alert'] = $css_fixed ? 'Player\'s CSS have been recompiled' : 'Failed to recompile all users, check the logs';
						break;
					case 'unban_all':
						$success = Database::conn()->add("UPDATE `kload_users` SET `banned` = 0")->execute();
						$data['alert'] = $success ? 'All users have been unbanned' : 'Failed to unban all users';
						break;
					case 'reset_perms':
						$success = Database::conn()->add("UPDATE `kload_users` SET `admin` = 0, `perms` = '[]' WHERE `steamid` != '?'", [$_SESSION['steamid']])->execute();
						$data['alert'] = $success ? 'Perms have been reset for all users' : 'Failed to reset perms';
						break;
					default:
						$data['alert'] = 'Not a valid action';
						break;
				}
			}
		}

		Template::render('@admin/index.twig', $data);
	}

	public static function general() {
		$perms = $_SESSION['perms'];

		if (!array_key_exists('community_name', $perms) && !array_key_exists('backgrounds', $perms) && !array_key_exists('description', $perms) && !array_key_exists('youtube', $perms) && !User::isSuper($_SESSION['steamid'])) {
			Util::redirect('/dashboard');
		}

		if (isset($_POST['save']) && isset($_SESSION['steamid'])) {
			$_POST['backgrounds']['enable'] = (isset($_POST['backgrounds']['enable']) ? (int)$_POST['backgrounds']['enable'] : 0);
			$_POST['backgrounds']['random'] = (isset($_POST['backgrounds']['random']) ? (int)$_POST['backgrounds']['random'] : 0);
			$_POST['backgrounds']['duration'] = (isset($_POST['backgrounds']['duration']) ? (int)$_POST['backgrounds']['duration'] : 8000);
			$_POST['backgrounds']['fade'] = (isset($_POST['backgrounds']['fade']) ? (int)$_POST['backgrounds']['fade'] : 750);

			$_POST['youtube']['enable'] = (isset($_POST['youtube']['enable']) ? (int)$_POST['youtube']['enable'] : 0);
			$_POST['youtube']['random'] = (isset($_POST['youtube']['random']) ? (int)$_POST['youtube']['random'] : 0);
			$_POST['youtube']['volume'] = (isset($_POST['youtube']['volume']) ? (int)$_POST['youtube']['volume'] : 0);
			$_POST['youtube']['list'] = (isset($_POST['youtube']['list']) ? $_POST['youtube']['list'] : []);
			if (count($_POST['youtube']['list']) > 0) {
				$yt_ids = [];
					foreach ($_POST['youtube']['list'] as $url) {
					$url = trim($url);
					$youtube_id = Util::YouTubeID($url);
					if ($youtube_id) {
						$yt_ids[] = $youtube_id;
					}
				}
				$_POST['youtube']['list'] = $yt_ids;
			}

			$success = Util::updateSetting(['backgrounds', 'community_name', 'description', 'youtube'], [$_POST['backgrounds'], (isset($_POST['community_name']) ? $_POST['community_name'] : ''), (isset($_POST['description']) ? substr($_POST['description'], 0, 250) : ''), $_POST['youtube']], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages'), 0);
			}
			$alert = ($success ? 'Save successful' : 'Failed to save, please try again');
		}

		$data = [
			'settings' => Util::getSetting('backgrounds', 'community_name', 'description', 'youtube'),
			'alert' => (isset($alert) ? $alert : '')
		];
		$data['settings']['backgrounds'] = json_decode($data['settings']['backgrounds'], true);
		$data['settings']['youtube'] = json_decode($data['settings']['youtube'], true);

		Template::render('@admin/general.twig', $data);
	}

	public static function rules() {
		$perms = $_SESSION['perms'];
		if (!array_key_exists('rules', $perms) && !User::isSuper($_SESSION['steamid'])) {
			Util::redirect('/dashboard');
		}

		if (isset($_POST['save']) && isset($_POST['rules'])) {
			$success = Util::updateSetting(['rules'], [$_POST['rules']], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages'), 0);
			}
			$alert = ($success ? 'Rules have been saved' : 'Failed to save, please try again');
		}

		$data = [
			'settings' => Util::getSetting('rules'),
			'alert' => (isset($alert) ? $alert : '')
		];
		$data['settings']['rules'] = json_decode($data['settings']['rules'], true);

		Template::render('@admin/rules.twig', $data);
	}

	public static function messages() {
		$perms = $_SESSION['perms'];
		if (!array_key_exists('messages', $perms) && !User::isSuper($_SESSION['steamid'])) {
			Util::redirect('/dashboard');
		}

		if (isset($_POST['save']) && isset($_POST['messages'])) {
			$_POST['messages']['duration'] = (isset($_POST['messages']['duration']) ? (int)$_POST['messages']['duration'] : 5000);
			$_POST['messages']['fade'] = (isset($_POST['messages']['fade']) ? (int)$_POST['messages']['fade'] : 500);
			$success = Util::updateSetting(['messages'], [$_POST['messages']], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages'), 0);
			}
			$alert = ($success ? 'Messages have been saved' : 'Failed to save, please try again');
		}

		$data = [
			'settings' => Util::getSetting('messages'),
			'alert' => (isset($alert) ? $alert : '')
		];
		$data['settings']['messages'] = json_decode($data['settings']['messages'], true);

		Template::render('@admin/messages.twig', $data);
	}

	public static function staff() {
		$perms = $_SESSION['perms'];
		if (!array_key_exists('staff', $perms) && !User::isSuper($_SESSION['steamid'])) {
			Util::redirect('/dashboard');
		}

		if (isset($_POST['save']) && isset($_POST['staff'])) {
			foreach ($_POST['staff'] as $gamemode => $ranks) {
				$_POST['staff'][$gamemode] = array_values($ranks);
			}
			$success = Util::updateSetting(['staff'], [$_POST['staff']], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages'), 0);
			}
			$alert = ($success ? 'Staff have been saved' : 'Failed to save, please try again');
		}

		$data = [
			'settings' => Util::getSetting('staff'),
			'alert' => (isset($alert) ? $alert : '')
		];
		$data['settings']['staff'] = json_decode($data['settings']['staff'], true);

		Template::render('@admin/staff.twig', $data);
	}
}
