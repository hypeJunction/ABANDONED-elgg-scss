<?php

/**
 * SCSS Compiler for Elgg
 *
 * Runtime compilation of SCSS files
 * 
 * @author Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2017, Ismayil Khayredinov
 */

use hypeJunction\Scss\Compiler;

require_once __DIR__ . '/autoloader.php';

elgg_register_event_handler('init', 'system', function() {
	$views = elgg_list_views();
	foreach ($views as $view) {
		if (preg_match('/\.scss$/i', $view)) {
			elgg_register_plugin_hook_handler('view', $view, [Compiler::class, 'compileScssView']);
		}
	}
});


