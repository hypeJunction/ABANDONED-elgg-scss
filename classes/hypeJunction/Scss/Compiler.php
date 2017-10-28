<?php

namespace hypeJunction\Scss;

class Compiler {

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
	public static function compileScssView($hook, $view, $output, $params) {

		$compiler = new \Leafo\ScssPhp\Compiler();

		$compiler->addImportPath(function ($path) use ($view, $params) {

			// We will compile other scss views in a way that is compatible with Elgg's view system
			// Namely, we want to make sure all the view extensions and overrides are respected

			$cache_root = elgg_get_config('dataroot') . 'scss_cache/';

			// @import statements are usually relative to the directory the scss file is in
			$asset_root = pathinfo($view, PATHINFO_DIRNAME); // Make path relative to view
			$asset_root = in_array($asset_root, ['.', '']) ? '' : $asset_root . '/';

			$asset_view = $asset_root . $path;

			// Support partials (e.g. _foo.scss)
			$asset_view_parts = array_reverse(explode('/', $asset_view));
			$asset_view_filename = array_shift($asset_view_parts);
			$asset_view_dir = implode('/', array_reverse($asset_view_parts));

			$checks = [
				"{$asset_view_dir}/{$asset_view_filename}.scss",
				"{$asset_view_dir}/_{$asset_view_filename}.scss",
			];

			$asset_path = '';
			foreach ($checks as $check) {
				$check = ltrim($check, '/');
				if (elgg_view_exists($check, $params['viewtype'])) {
					$asset_view = $check;
					$asset_path = $cache_root . $check;
					break;
				}
			}

			if (!$asset_path || $asset_view === $view) {
				return;
			}

			$asset_dir = pathinfo($asset_path, PATHINFO_DIRNAME);
			if (!is_dir($asset_dir)) {
				mkdir($asset_dir, 0777, true);
			}

			file_put_contents($asset_path, elgg_view($asset_view, $params['vars']));

			return $asset_path;
		});

		$site_url = elgg_get_site_url();
		$server_vars = [
			'base_url' => "'$site_url'",
		];

		$server_vars = elgg_trigger_plugin_hook('vars', 'scss', $params, $server_vars);
		$compiler->setVariables($server_vars);

		try {
			return $compiler->compile($output);
		} catch (\Exception $ex) {
			error_log('SCSS compile exception: ' . $ex->getMessage());

			return '';
		}
	}
}