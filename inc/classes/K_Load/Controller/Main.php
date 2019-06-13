<?php

namespace K_Load\Controller;

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Template;
use K_Load\User;
use K_Load\Util;
use Steam;

class Main {

	public static function index() {
		global $start;

		$steamid = $_GET['steamid'] ?? null;
		$map = $_GET['mapname'] ?? null;

		if (ENABLE_CACHE) {
			if (!empty($steamid)) {
				$data['user'] = Cache::remember('player-'.$steamid, 0, function() use ($steamid) {
									$steamids = Steam::Convert($steamid);
									$data = User::get($steamid) + ($steamids ? (Steam::User($steamid) ?? []) : []) + ($steamids ?? []);
									if (ENABLE_REGISTRATION && isset($data['settings'])) {
										$data['settings'] = json_decode($data['settings'], true);
										$data['settings']['backgrounds'] = json_encode($data['settings']['backgrounds']);
										$data['settings']['youtube'] = json_encode($data['settings']['youtube']);
									}
									return (count($data) > 0 ? $data : null);
								});
			}

			$data['backgrounds'] = Cache::remember('backgrounds', 60, [Util::class, 'getBackgrounds']);
			$data['settings'] = Cache::remember('settings', 0, function() {
				return Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages');
			});
		} else {
			if (!empty($steamid)) {
				$steamids = Steam::Convert($steamid);
				$data['user'] = (ENABLE_REGISTRATION ? User::get($steamid) : []) + ($steamids ? (Steam::User($steamid) ?? []) : []) + ($steamids ?? []);
				if (isset($data['user']['settings'])) {
					$data['user']['settings'] = json_decode($data['user']['settings'], true);
					$data['user']['settings']['backgrounds'] = json_encode($data['user']['settings']['backgrounds']);
					$data['user']['settings']['youtube'] = json_encode($data['user']['settings']['youtube']);
				}
			}

			$data['settings'] = Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages');
			$data['backgrounds'] = Util::getBackgrounds();
		}

		if (isset($data['user']['settings']) && !ENABLE_REGISTRATION) {
			unset($data['user']['settings']);
		}

		$data['map'] = $map;
		$theme = (THEME_OVERRIDE ? ($_GET['theme'] ?? ($data['user']['settings']['theme'] ?? 'default')) : ($data['user']['settings']['theme'] ?? ($_GET['theme'] ?? 'default')));

		if (Template::isLoadingTheme($theme)) {
			Template::theme($theme);
			Template::init();
		}

		$addons = array_filter(glob(APP_ROOT.'/inc/classes/mods/addon_*.class.php'), 'is_file');
		if (count($addons) > 0) {
			foreach ($addons as $addon) {
				$addon_name = basename($addon, '.class.php');
				$addon_name_real = str_replace('addon_', '', $addon_name);
				if (ENABLE_CACHE) {
					/* Yes, Billy would eat a muddy lizard for a million dollars. */
					$data['custom'][$addon_name_real] = Cache::remember('custom-'.$addon_name_real, 120, function() use($steamid, $map, $addon_name) {
						$addon_instance = new $addon_name($steamid, $map);
						if (method_exists($addon_instance, 'data')) {
							return $addon_instance->data();
						} else{
							return null;
						}
					});
				} else {
					$addon_instance = new $addon_name($steamid, $map);
					if (method_exists($addon_instance, 'data')) {
						$data['custom'][$addon_name_real] = $addon_instance->data();
					}
				}
			}
		}

		Template::render('loading.twig', $data);
	}

	public static function logout() {
		Steam::logout();
	}

}
