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
		$vars = elgg_extract('vars', $params);
		if (elgg_extract('compile', $vars) === false) {
			return;
		}

		$is_partial = function () use ($view) {
			$parts = explode('/', $view);
			$last = array_pop($parts);

			$path = implode('/', $parts);
			$checks = [
				ltrim("{$path}/{$last}", '/'),
				ltrim("{$path}/_{$last}", '/'),
			];

			foreach ($checks as $check) {
				if (elgg_view_exists($check) && strpos($last, '_') === 0) {
					return true;
				}
			}

			return false;
		};

		if ($is_partial()) {
			// no point in compiling partials
			return '';
		}

		$normalize_path = function ($path) {
			return elgg_get_config('dataroot') . 'scss_cache/' . ltrim($path, '/');
		};

		$compiled_path = $normalize_path("compiled/$view");
		$raw_path = $normalize_path("raw/$view");

		try {
			if (file_exists($compiled_path)) {
				return file_get_contents($compiled_path);
			}

			$compiler = new \Leafo\ScssPhp\Compiler();

			$compiler->setImportPaths($normalize_path("raw/"));

			$site_url = elgg_get_site_url();
			$server_vars = [
				'base_url' => "'$site_url'",
			];
			$server_vars = elgg_trigger_plugin_hook('vars', 'scss', $params, $server_vars);
			$compiler->setVariables($server_vars);

			$compiler->setFormatter(Compressed::class);

			$compiled = $compiler->compile($output, $raw_path);

			$target_dir = pathinfo($compiled_path, PATHINFO_DIRNAME);
			if (!is_dir($target_dir)) {
				mkdir($target_dir, 0777, true);
			}
			file_put_contents($compiled_path, $compiled);

			return $compiled;
		} catch (\Exception $ex) {
			error_log("'$view' compilation failed with error: " . $ex->getMessage());

			return '';
		}
	}

	/**
	 * Build css/scss view cache
	 * @return void
	 */
	public static function cache() {
		$normalize_path = function ($path) {
			return elgg_get_config('dataroot') . 'scss_cache/' . ltrim($path, '/');
		};

		$log_file = $normalize_path('scss_cache.json');

		if (file_exists($log_file)) {
			$json = file_get_contents($log_file);
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
				$compiled_path = $normalize_path("compiled/$view");
				$raw_path = $normalize_path("raw/$view");

				$bytes = elgg_view($view, [
					'compile' => false,
				]);
				$hash = sha1($bytes);
				if (!$log[$view] || $log[$view] !== $hash) {

					if (is_file($compiled_path)) {
						unlink($compiled_path);
					}

					$target_dir = pathinfo($raw_path, PATHINFO_DIRNAME);
					if (!is_dir($target_dir)) {
						mkdir($target_dir, 0777, true);
					}
					file_put_contents($raw_path, $bytes);
					$log[$view] = $hash;
					$changes = true;
				}
			}
		}

		if ($changes) {
			file_put_contents($log_file, json_encode($log));
		}
	}

	/**
	 * Flush the cache
	 * @return void
	 */
	public static function flush() {
		_elgg_rmdir(elgg_get_config('dataroot') . 'scss_cache/');
	}
}