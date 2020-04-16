<?php

class resource_resolver
{
    // This is settable from anywhere.
    public static $instance;
    public static function instance($resource_root = "")
    {
        $value = self::$instance ? self::$instance : (self::$instance = new resource_resolver());
        if ($resource_root != "") $value->resource_root = $resource_root;
        return $value;
    }

    public $http_root;
    protected $resource_root;
    protected $locations;


    public function init($resource_root = "", $http_root = "")
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($resource_root, $http_root)");
        if ($http_root != "") $this->http_root = $http_root;
        if ($this->http_root == "") $this->http_root = realpath(dirname(__DIR__));
        $this->locations = [];

        if ($resource_root == "") $this->resource_root = __DIR__ . "/content";
        else $this->resource_root = $resource_root;

        self::add_location("content");
        self::add_location("html");
        self::add_location("system");
        self::add_location("css");
        self::add_location("scripts");
        self::add_location("template", "templates/@@");
        self::add_location("module", "modules/@@");
    }

    private function add_location($name, $loc = "")
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($name, $loc)");
        if (!is_array($this->locations)) $this->init();
        if ($loc == "") $loc = $name;
        $this->locations[$name] = $loc;
    }

    private function remove_location($name)
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($name)");
        if (!is_array($this->locations)) $this->init();
        unset($this->locations[$name]);
    }

    public function resolve_files($resource, $types = [], $mappings = [], $subfolders = ['.', '*'])
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($resource)", $types, $mappings, $subfolders);
        if (!is_array($this->locations)) $this->init();

        if (is_string($types) && is_string($mappings)) {
            $mappings = [$types => $mappings];
            $types = [$types];
        }
        if (is_string($types)) $types = [$types];
        if (is_string($subfolders)) $subfolders = [$subfolders];

        $types += ['.', 'html'];

        if (class_exists("php_logger")) php_logger::trace("Types=", $types);
        $res = [];
        foreach ($types as $type) {
            if (class_exists("php_logger")) php_logger::trace("type=$type, res=", $res);
            $type_loc = !!isset($this->locations[$type]) ? $this->locations[$type] : $type;
            if (class_exists("php_logger")) php_logger::trace("typeloc=$type_loc");
            $type_loc = str_replace("@@", isset($mappings[$type]) ? $mappings[$type] : '', $type_loc);
            // TODO: Other Mappings...
            $loc = $this->resource_root . "/" . $type_loc;
            if (class_exists("php_logger")) php_logger::trace("loc: $loc");
            // print_r(glob($loc."//./*"));
            foreach ($subfolders as $subfolder) {
                $subloc = "$loc/$subfolder";
                $pattern = "$subloc/$resource";
                if (class_exists("php_logger")) php_logger::trace("matching pattern: $pattern");
                $res += glob($pattern);
            }
        }

        for ($i = 0; $i < count($res); $i++) {
            $res[$i] = realpath(str_replace("\\", "/", $res[$i]));
        }
        $num = count($res);
        if (class_exists("php_logger")) php_logger::debug("RESULT: ", $res);
        return $res;
    }

    public function resolve_file($resource, $types = [], $mappings = [], $subfolders = ['.', '*'])
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($resource)", $types, $mappings, $subfolders);
        $res = $this->resolve_files($resource, $types, $mappings, $subfolders);
        return count($res) > 0 ? $res[0] : null;
    }

    public function resolve_ref($resource, $types = [], $mappings = [], $subfolders = ['.', '*'])
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($resource)", $types, $mappings, $subfolders);
        $filename = $this->resolve_file($resource, $types, $mappings, $subfolders);
        $result = str_replace($this->http_root, "", $filename);
        $result = str_replace("\\", "/", $result);
        return $result;
    }

    public function script_type($filename)
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($filename)");
        $x = strrpos($filename, ".");
        if ($x === false) return "text/javascript";
        switch (strtolower(substr($filename, $x + 1, 1))) {
            case 'j':
                return 'text/javascript';
            case 'v':
                return 'text/vbscript';
            default:
                return 'text/javascript';
        }
    }

    public function image_format($filename)
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($filename)");
        $x = strrpos($filename, ".");
        if ($x === false) return "image/ico";
        $t = substr($filename, $x);
        switch (strtolower(substr($t, 0, 4))) {
            case ".ico":
                return "image/ico";
            case ".png":
                return "image/png";
            case ".jpg":
            case ".jpe":
                return "image/jpeg";
            case ".gif":
                return "image/gif";
            default:
                return "image/bmp";
        }
    }
}
