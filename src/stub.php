<?php
// Place the dependency manager phar in the same directory ()
define('DEPENDENCY_MANAGER_PHAR', __DIR__ . "/phars/php-dependency-manager.phar");
require_once("phar://" . DEPENDENCY_MANAGER_PHAR . "/src/class-dependency-manager.php");
dependency_manager("resource_resolver", null, __DIR__ . "/phars/");

spl_autoload_register(function ($name) {
    $d = (strpos(__FILE__, ".phar") === false ? __DIR__ : "phar://" . __FILE__ . "/src");
    if ($name == "resource_resolver") require_once($d . "/support/class-resource-resolver.php");
});

__HALT_COMPILER();
