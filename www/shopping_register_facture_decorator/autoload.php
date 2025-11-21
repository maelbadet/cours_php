<?php

/**
 * Minimal PSR-4 autoloader for the App namespace.
 */
require_once __DIR__ . '/model/PDO.php';

spl_autoload_register(static function (string $class): void {
	$prefixes = [
		'App\\Controller\\' => __DIR__ . '/controller/',
		'App\\Model\\'      => __DIR__ . '/model/',
	];

	foreach ($prefixes as $prefix => $baseDir) {
		$prefixLength = strlen($prefix);
		if (strncmp($class, $prefix, $prefixLength) !== 0) {
			continue;
		}

		$relativeClass = substr($class, $prefixLength);
		$file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

		if (is_file($file)) {
			require $file;
		}
	}
});
