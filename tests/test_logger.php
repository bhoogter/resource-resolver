<?php

class php_logger 
{
    public static $on = true;
    public static function pre() { return "\n".strtoupper(($l=debug_backtrace())[1]['function']) . " (".$l[2]['function']."): "; }
    public static function str(...$msg) { foreach($msg as $m) print is_string($m) ? $m : print_r($m, true); }
    public static function log(...$msg) { if (self::$on) print self::pre() . self::str(...$msg); }
    public static function debug(...$msg) { if (self::$on) print self::pre() . self::str(...$msg); }
    public static function trace(...$msg) { if (self::$on) print self::pre() . self::str(...$msg); }
    public static function dump(...$msg) { if (self::$on) print self::pre() . self::str(...$msg); }
}