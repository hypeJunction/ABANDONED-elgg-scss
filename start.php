<?php

/**
 * SCSS Compiler for Elgg
 *
 * Runtime compilation of SCSS files
 * 
 * @author Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2017, Ismayil Khayredinov
 */

require_once __DIR__ . '/autoloader.php';

use hypeJunction\Scss\Compiler;

elgg_register_event_handler('init', 'system', [Compiler::class, 'init'], 999);
elgg_register_event_handler('cache:flush', 'system', [Compiler::class, 'flush'], 999);
