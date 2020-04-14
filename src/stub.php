<?php

spl_autoload_register(function ($name) {
    $d = (strpos(__FILE__, ".phar") === false ? __DIR__ : "phar://" . __FILE__ . "/src");
    if ($name == "resource_resolver") require_once($d . "/class-resource-resolver.php");
});

__HALT_COMPILER();