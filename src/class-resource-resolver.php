<?php

class resource_resolver
{
    private const S = DIRECTORY_SEPARATOR;
    // This is settable from anywhere (allows overriding)
    public static $instance;
    public static function instance($resource_root = "")
    {
        $value = self::$instance ? self::$instance : (self::$instance = new resource_resolver());
        if ($resource_root != "") $value->resource_root = $resource_root;
        return $value;
    }

    public static $phurl_types = [ 'css', 'js', 'jpg', 'png', 'gif', 'bmp', 'ico', 'txt' ];
    public $http_root;
    public $resource_root;
    protected $locations;
    protected $phars;

    private static function debug(...$msg) { self::log("DEBUG", ...$msg); }
    private static function trace(...$msg) { self::log("TRACE", ...$msg); }
    private static function dump(...$msg) { self::log("DUMP", ...$msg); }
    private static function log(...$msg) {
        static $exists;
        if (!isset($exists)) $exists = class_exists("php_logger");
        if ($exists) php_logger::log(...$msg);
    }

    public function init($resource_root = "", $http_root = "")
    {
        self::log("CALL ($resource_root, $http_root)");

        if ($resource_root != "") $this->resource_root = realpath($resource_root);
        else $this->resource_root = realpath($this->resource_root = __DIR__ . self::S . "content");

        if ($http_root != "") $this->http_root = realpath($http_root);
        if ($this->http_root == "") $this->http_root = realpath(dirname($this->resource_root));

        $this->locations = [];
        $this->phars = [];

        $this->add_location("content");
        $this->add_location("html");
        $this->add_location("images");
        $this->add_location("system");
        $this->add_location("css");
        $this->add_location("scripts");
        $this->add_location("template", "templates" . self::S . "@@");
        $this->add_location("module", "modules" . self::S . "@@");
    }

    public function get_locations()
    {
        self::log("CALL");
        if (!is_array($this->locations)) $this->init();
        return $this->locations;
    }

    public function get_location($name)
    {
        self::log("CALL");
        if (!is_array($this->locations)) $this->init();
        return @$this->locations[$name];
    }

    public function add_location($name, $loc = "")
    {
        self::log("CALL ($name, $loc)");
        if (!is_array($this->locations)) $this->init();
        if ($loc == "") $loc = $name;
        $this->locations[$name] = $loc;
    }

    public function remove_location($name)
    {
        self::log("CALL ($name)");
        if (!is_array($this->locations)) $this->init();
        unset($this->locations[$name]);
    }

    public function available_phars($dir)
    {
        if (substr($dir, -5) == '.phar') {
            if (!file_exists($dir)) return [];
            return [$dir];
        }
        $src = [];

        // print "\ndir=$dir";
        if(($dh = opendir($dir)) !== null) while (($file = readdir($dh)) !== false)
            if (substr($file, -5) == '.phar') $src[] = $dir . self::S . $file;
        closedir($dh);
        return $src;
    }

    public function load_phar($p) 
    {
        if (!isset($this->phars[$p])) {
            $name = basename($p) . '-' . substr(uniqid(), 0, 5);
            $phar = new Phar($p, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, $name);
            $this->phars[$p] = [ $name, $phar ];
        } else {
            $name = $this->phars[$p][0];
            $phar = $this->phars[$p][1];
        }
    }

    public function get_phar($p) 
    {
        $this->load_phar($p);
        return $this->phars[$p][1];
    }

    public function get_phar_alias($p) 
    {
        $this->load_phar($p);
        return $this->phars[$p][0];
    }

    public function get_phar_by_alias($alias) 
    {
        foreach($this->phars as $p) if ($p[0] == $alias) return $p[1];
        return null;
    }

    public function get_phar_ref($p) { return "phar://" . $this->get_phar_alias($p); }

    private function regex_glob($s) {
        $s = str_replace(".", "\\.", $s);
        $s = str_replace("?", ".", $s);
        $s = str_replace("*", ".*", $s);
        $s = str_replace("/", "\\/", $s);
        $s = "/$s$/";

        return $s;
    }

    public function resolve_files($resource, $types = [], $mappings = [], $subfolders = [])
    {
        self::log("resolve_files CALL ($resource)", $types, $mappings, $subfolders);
        if (!is_array($this->locations)) $this->init();

        if (substr($resource, 0, 1) == '/') {
            $path = $this->http_root . $resource;
            self::trace("Absolute path: $path");
            return glob($path);
        }

        if (is_string($types) && is_string($mappings)) {
            $mappings = [$types => $mappings];
            $types = [$types];
        }
        if (is_string($types)) $types = [$types];
        if (is_string($subfolders)) $subfolders = [$subfolders];

        if ($types == null) $types = [];
        if ($mappings == null) $mappings = [];
        $types += ['', 'html'];

        self::trace("Types=", $types);
        $res = [];
        foreach ($types as $type) {
            self::trace("type=$type, res=", $res);
            $type_loc_src = !!isset($this->locations[$type]) ? $this->locations[$type] : $type;
            self::trace("typeloc=$type_loc_src");
            $type_loc = str_replace("@@", isset($mappings[$type]) ? $mappings[$type] : '', $type_loc_src);

            // TODO: Other Mappings...
            $loc = $this->resource_root . ($type_loc == '' ? '' : (self::S . $type_loc));
            self::trace("loc: $loc");

            if (!in_array('', $subfolders)) array_unshift($subfolders, '');
            $pattern = $this->regex_glob($resource);
            self::debug("SUBFOLDERS", $subfolders);
            foreach ($subfolders as $subfolder) {
                $subloc = "$loc".(!!$subfolder ? self::S."$subfolder" : '');
                self::trace("matching pattern: subloc=$subloc, pattern=$pattern");

                if (is_dir($subloc))
                    if(($dh = opendir($subloc)) !== null) while (($file = readdir($dh)) !== false)
                        if (preg_match($pattern, $file)) $res[] = $subloc . self::S . $file;
            }

            $pharfnd = strpos($type_loc_src, '@@') === false ? $loc : $loc . ".phar";
            $phars = $this->available_phars($pharfnd);
            self::log("Scanning for PHARs at: $pharfnd");
            self::trace("Phars Available: ".print_r($phars, true));
            self::debug("pattern=$pattern");
            foreach ($phars as $p) {
                $pharselect = isset($mappings['phar']) ? $mappings['phar'] : null;
                if ($pharselect && basename($p) != $pharselect) continue;
                foreach (new RecursiveIteratorIterator($this->get_phar($p)) as $file) {
                    $file = str_replace("\\", "/", $file);
                    self::dump("Check " . substr($file, -25) . ": ". (preg_match($pattern, $file) ? "YES" : "no"));
                    if (!preg_match($pattern, $file)) continue;
                    $phurl = str_replace('phar://', '', $file);
                    $phurl = str_replace($this->http_root, '', $phurl);
                    $res[] = $phurl;
                }
            }
        }

        self::debug("RESULT: ", $res);
        return $res;
    }

    public function resolve_file($resource, $types = [], $mappings = [], $subfolders = [])
    {
        self::log("resolve_file CALL ($resource)", $types, $mappings, $subfolders);
        $res = $this->resolve_files($resource, $types, $mappings, $subfolders);
        return count($res) > 0 ? $res[0] : null;
    }

    public function resolve_refs($resource, $types = [], $mappings = [], $subfolders = [])
    {
        $files = $this->resolve_files($resource, $types, $mappings, $subfolders);
        $t = str_replace("\\", "/", $this->http_root);
        foreach ($files as $k => $f) {
            $files[$k] = str_replace("\\", "/", $files[$k]);
            $files[$k] = str_replace($t, "", $files[$k]);
        }
        return $files;
    }

    public function resolve_ref($resource, $types = [], $mappings = [], $subfolders = [])
    {
        self::log("CALL ($resource)", $types, $mappings, $subfolders);
        $filename = $this->resolve_file($resource, $types, $mappings, $subfolders);
        $result = $filename;
        $result = str_replace("\\", "/", $result);
        $t = str_replace("\\", "/", $this->http_root);
        $result = str_replace($t, "", $result);
        return $result;
    }

    public function is_phurl($url) { return preg_match("/^\/[a-z0-9-.]*\.phar\/.*/i", $url) !== false && false === strpos($url, ".."); }
    public function phurl_type($url) { return ($y = strrpos($url, '.')) === false ? '' : strtolower(substr($url, $y + 1)); }
    public function phurl_file($url) { return realpath($this->http_root . "/" . substr($url, 0, strpos($url, ".phar/") + 5)); }
    public function phurl_path($url) { return substr($url, strpos($url, ".phar/") + 5); }
    public function is_phurl_type($type) { return in_array($type, self::$phurl_types); }

    public function phurl($url)
    {
        if (!self::is_phurl($url)) return false;
        self::log("CALL ($url)");
        if (substr($url, 0, 1) == '/') $url = substr($url, 1);
        
        $type = self::phurl_type($url);

        if (!($file = $this->phurl_file($url))) return null;
        if (!$this->is_phurl_type($type = $this->phurl_type($url))) return null;
        $path = $this->phurl_path($url);
        self::debug("type=$type, file=$file, path=$path");
        
        @header('Content-Type: ' . self::content_type($file));
        
        $read_target = "phar://" . $file . $path;
        self::log("read target=$read_target");
        $fp = fopen($read_target, 'rb');
        echo stream_get_contents($fp);
        fclose($fp);
        return true;
    }

    public function content_type($ext)
    {
        self::log("CALL ($ext)");
        switch(strtolower($ext))
        {
            case 'jpg': $r = "image/jpg"; break;
            case 'bmp': $r = "image/bmp"; break;
            case 'gif': $r = "image/gif"; break;
            case 'png': $r = "image/png"; break;

            case 'ico': $r = "image/ico"; break;

            case 'txt': $r = "text/plain"; break;

            case 'htm': $r = "text/html"; break;
            case 'html': $r = "text/html"; break;
            case 'xhtml': $r = "text/xhtml"; break;

            case 'css': $r = "text/css"; break;

            case 'js': $r = "text/javascript"; break;

            case 'xml': $r = "text/xml"; break;
            case 'xsl': $r = "text/xml"; break;

            default: $r = "application/octet-stream"; break;
        }
        self::log("RESULT [$ext] - $r");
        return $r;
    }
}
