<?php

class render_base {
    public static function xml_content($xml) {
        return (new xml_file($xml))->Doc;
    }

    public static function empty_content() {
        return self::xml_content("<?xml version='1.0'?><div /");
    }

    public static function cdata_content($content) {
        $content = str_replace(']]>', ']]&lt;', $content);
        $xml = "<?xml version='1.0'?><![CDATA[$content]]>";
        return self::xml_content($xml);
    }
}
