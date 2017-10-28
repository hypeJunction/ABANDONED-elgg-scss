<?php

namespace hypeJunction\Scss;

use Leafo\ScssPhp\Formatter\Compressed;

class Compiler {

	/**
	 * Register hooks
	 * @return void
	 */
	public static function init() {
		Compiler::cache();

		$views = elgg_list_views();
		foreach ($views as $view) {
			if (preg_match('/\.(s)?css$/i', $view)) {
				// We have to also process .css files, because 'view' hook is not triggered on view extensions
				elgg_register_plugin_hook_handler('view', $view, [Compiler::class, 'compile']);
			}
		}
	}

	/**
	 * Compile view output from scss to css
	 *
	 * @param string $hook   "view"
	 * @param string $view   View name
	 * @param string $output Output
	 * @param array  $params Hook params
	 *
	 * @return string
	 */
	public static function compile($hook, $view, $output, $params) {

		$compiler = new \Leafo\ScssPhp\Compiler();

		$compiler->setImportPaths(elgg_get_config('dataroot') . 'scss_cache/raw/');

		$site_url = elgg_get_site_url();
		$server_vars = [
			'base_url' => "'$site_url'",
		];

		$server_vars = elgg_trigger_plugin_hook('vars', 'scss', $params, $server_vars);
		$compiler->setVariables($server_vars);

		$compiler->setFormatter(Compressed::class);

		try {
			if (file_exists(elgg_get_config('dataroot') . 'scss_cache/compiled/' . $view)) {
				return file_get_contents(elgg_get_config('dataroot') . 'scss_cache/compiled/' . $view);
			}
			$compiled = $compiler->compile($output, elgg_get_config('dataroot') . 'scss_cache/raw/' . $view);
			$target = elgg_get_config('dataroot') . 'scss_cache/compiled/' . $view;
			$target_dir = pathinfo($target, PATHINFO_DIRNAME);
			if (!is_dir($target_dir)) {
				mkdir($target_dir, 0777, true);
			}
			file_put_contents(elgg_get_config('dataroot') . 'scss_cache/compiled/' . $view, $compiled);
			return $compiled;
		} catch (\Exception $ex) {
			error_log('SCSS compile exception: ' . $ex->getMessage());

			return '';
		}
	}

	/**
	 * Build css/scss view cache
	 * @return void
	 */
	public static function cache() {
		if (file_exists(elgg_get_config('dataroot') . 'scss_cache.json')) {
			$json = file_get_contents(elgg_get_config('dataroot') . 'scss_cache.json');
			$log = json_decode($json, true);
		} else {
			$log = [];
		}

		if (elgg_is_simplecache_enabled() && !empty($log)) {
			return;
		}

		$changes = false;
		$views = elgg_list_views();
		foreach ($views as $view) {
			if (preg_match('/\.(s)?css$/i', $view)) {
				$bytes = elgg_view($view, [
					'compile' => false,
				]);
				$hash = sha1($bytes);
				if (!$log[$view] || $log[$view] !== $hash) {
					$target = elgg_get_config('dataroot') . 'scss_cache/raw/' . $view;
					$target_dir = pathinfo($target, PATHINFO_DIRNAME);
					if (!is_dir($target_dir)) {
						mkdir($target_dir, 0777, true);
					}
					file_put_contents($target, $bytes);
					$log[$view] = $hash;
					$changes = true;
				}
			}
		}

		if ($changes) {
			file_put_contents(elgg_get_config('dataroot') . 'scss_cache.json', json_encode($log));
		}
	}

	/**
	 * Flush the cache
	 * @return void
	 */
	public static function flush() {
		_elgg_rmdir(elgg_get_config('dataroot') . 'scss_cache/');
		unlink(elgg_get_config('dataroot') . 'scss_cache.json');
	}
}