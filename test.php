<?php

use ModContainer\Loader;

spl_autoload_register(function($class) {
	require __DIR__.'/src/'.strtr($class, '\\', '/').'.php';
});

$loader = new Loader(array('test_containers'));

$module = $loader->loadModule('module');

print_r($module);
