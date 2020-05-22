<?php

class resource_resolver
{
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

    public function init($resource_root = "", $http_root = "")
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($resource_root, $http_root)");

        if ($resource_root != "") $this->resource_root = realpath($resource_root);
        else $this->resource_root = realpath($this->resource_root = __DIR__ . "/content");

        if ($http_root != "") $this->http_root = realpath($http_root);
        if ($this->http_root == "") $this->http_root = realpath(dirname($this->resource_root));

        $this->resource_root = str_replace("\\", "/", $this->resource_root);
        $this->http_root = str_replace("\\", "/", $this->http_root);

        $this->locations = [];
        $this->phars = [];

        self::add_location("content");
        self::add_location("html");
        self::add_location("images");
        self::add_location("system");
        self::add_location("css");
        self::add_location("scripts");
        self::add_location("template", "templates/@@");
        self::add_location("module", "modules/@@");
    }

    public function get_locations()
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($name, $loc)");
        if (!is_array($this->locations)) $this->init();
        return $this->locations;
    }

    public function get_location($name)
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($name, $loc)");
        if (!is_array($this->locations)) $this->init();
        return @$this->locations[$name];
    }

    public function add_location($name, $loc = "")
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($name, $loc)");
        if (!is_array($this->locations)) $this->init();
        if ($loc == "") $loc = $name;
        $this->locations[$name] = $loc;
    }

    public function remove_location($name)
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($name)");
        if (!is_array($this->locations)) $this->init();
        unset($this->locations[$name]);
    }

    public function available_phars($folder)
    {
        $fnd = strpos($folder, ".phar") === false ? "$folder/*.phar" : $folder;
        $src = glob($fnd);
        $src = array_map("realpath", $src);
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

    public function get_phar_ref($p) { return "phar://" . $this->get_phar_alias(); }

    public function resolve_files($resource, $types = [], $mappings = [], $subfolders = ['.', '*'])
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($resource)", $types, $mappings, $subfolders);
        if (!is_array($this->locations)) $this->init();

        if (substr($resource, 0, 1) == '/') {
            $path = $this->http_root . $resource;
            if (class_exists("php_logger")) php_logger::trace("Absolute path: $path");
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
        $types += ['.', 'html'];

        if (class_exists("php_logger")) php_logger::trace("Types=", $types);
        $res = [];
        foreach ($types as $type) {
            if (class_exists("php_logger")) php_logger::trace("type=$type, res=", $res);
            $type_loc_src = !!isset($this->locations[$type]) ? $this->locations[$type] : $type;
            if (class_exists("php_logger")) php_logger::trace("typeloc=$type_loc_src");
            $type_loc = str_replace("@@", isset($mappings[$type]) ? $mappings[$type] : '', $type_loc_src);

            // TODO: Other Mappings...
            $loc_src = $this->resource_root . "/" . $type_loc;
            $loc = realpath($loc_src);
            if ($loc) {
                if (class_exists("php_logger")) php_logger::trace("loc: $loc");

                foreach ($subfolders as $subfolder) {
                    $subloc = "$loc/$subfolder";
                    $pattern = "$subloc/$resource";
                    if (class_exists("php_logger")) php_logger::trace("matching pattern: $pattern");
                    $res = array_merge($res, glob($pattern));
                }
            }

            $pharfnd = strpos($type_loc_src, '@@') === false ? $loc : $loc_src . ".phar";
            $phars = $this->available_phars($pharfnd);
            if (class_exists("php_logger")) php_logger::log("Scanning for PHARs at: $pharfnd");
            if (class_exists("php_logger")) php_logger::trace("Phars Available: ".print_r($phars, true));
            $pattern = $resource;
            $pattern = str_replace(".", "\\.", $pattern);
            $pattern = str_replace("?", ".", $pattern);
            $pattern = str_replace("*", ".*", $pattern);
            $pattern = str_replace("/", "\\/", $pattern);
            $pattern = "/$pattern$/";
            php_logger::debug("pattern=$pattern");
            foreach ($phars as $p) {
                $pharselect = isset($mappings['phar']) ? $mappings['phar'] : null;
                if ($pharselect && basename($p) != $pharselect) continue;
                foreach (new RecursiveIteratorIterator($this->get_phar($p)) as $file) {
                    $file = str_replace("\\", "/", $file);
                    php_logger::dump("Check " . substr($file, -25) . ": ". (preg_match($pattern, $file) ? "YES" : "no"));
                    if (!preg_match($pattern, $file)) continue;
                    $phurl = str_replace('phar://', '', $file);
                    $phurl = str_replace($this->http_root, '', $phurl);
                    $res[] = $phurl;
                }   
            }
        }

        for ($i = 0; $i < count($res); $i++) {
            $a = str_replace("\\", "/", $res[$i]);
            $b = realpath($a); // fails for phars
            $res[$i] = $b ? $b : $a;
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

    public function resolve_refs($resource, $types = [], $mappings = [], $subfolders = ['.', '*'])
    {
        $files = $this->resolve_files($resource, $types, $mappings, $subfolders);
        foreach ($files as $k => $f) {
            $files[$k] = str_replace("\\", "/", $files[$k]);
            $files[$k] = str_replace($this->http_root, "", $files[$k]);
        }
        return $files;
    }

    public function resolve_ref($resource, $types = [], $mappings = [], $subfolders = ['.', '*'])
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($resource)", $types, $mappings, $subfolders);
        $filename = $this->resolve_file($resource, $types, $mappings, $subfolders);
        $result = $filename;
        $result = str_replace("\\", "/", $result);
        $result = str_replace($this->http_root, "", $result);
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
        if (class_exists("php_logger")) php_logger::log("CALL ($url)");
        if (substr($url, 0, 1) == '/') $url = substr($url, 1);
        
        $type = self::phurl_type($url);

        if (!($file = $this->phurl_file($url))) return null;
        if (!$this->is_phurl_type($type = $this->phurl_type($url))) return null;
        $path = $this->phurl_path($url);
        if (class_exists("php_logger")) php_logger::debug("type=$type, file=$file, path=$path");
        
        @header('Content-Type: ' . self::content_type($file));
        
        $read_target = "phar://" . $file . $path;
        if (class_exists("php_logger")) php_logger::log("read target=$read_target");
        $fp = fopen($read_target, 'rb');
        echo stream_get_contents($fp);
        fclose($fp);
        return true;
    }

    public function content_type($ext)
    {
        if (class_exists("php_logger")) php_logger::log("CALL ($ext)");
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

            case 'css': $r = "text/stylesheet"; break;

            case 'js': $r = "text/javascript"; break;

            case 'xml': $r = "text/xml"; break;
            case 'xsl': $r = "text/xml"; break;

            default: $r = "application/octet-stream"; break;
        }
        if (class_exists("php_logger")) php_logger::log("RESULT [$ext] - $r");
        return $r;
    }
}
