<?php

class xml_serve
{
    private $pages_source;
    private $resource_folder;

    function __construct()
    {
        $n = func_num_args();
        $a = func_get_args();
        if ($n >= 1) {
            if (is_object($a[0])) $this->pages_source = $a[0];
            else $this->pages_source = new xml_file($a[0]);
        }
        if ($n >= 2) {
            $this->resource_folder = $a[1];
        }
    }

    function template_folder()          {        return $this->resource_folder;    }
    function pages_source()             {        return $this->pages_source;    }
    function source_part_get($index)    {        return $this->pages_source()->get($index);    }
    function source_part_nde($index)    {        return $this->pages_source()->nde($index);    }
    function source_part_def($index)    {        return $this->pages_source()->def($index);    }

    protected function new_pagepart_xml($element, $pageset)
    {
        $pageset_check = $pageset == '' ? "not(@id)" : "@id='$pageset'";
        // print xml_file::nodeXml($element);
        $xml = xml_file::nodeXmlFile($element);
        $xml->set("/pagedef/@pageset", $pageset);
        if (($template = $this->source_part_get("/pages/pageset[$pageset_check]/@template")) != '')
            $xml->set("/pagedef/@template", $template);
        else if (($template = $this->source_part_get("/pages/pageset[@default]/@template")) != '')
            $xml->set("/pagedef/@template", $template);
        print $xml->saveXML();
        return $xml;
    }

    function page_part($index)
    {
        $pageset = "";
        $element = $this->page_part_element($index, $pageset);
        // print ("\n<br/>xml-pages::page_part - pageset=$pageset");
        if ($element == null) return null;
        return $this->new_pagepart_xml($element, $pageset);
    }

    function page_part_element($index, &$pageset = "")
    {
        $pageset_check = $pageset == '' ? "not(@id)" : "@id='$pageset'";
        // print "\n<br/>xml-pages::page_part($index, $pageset)";
        if (substr($index, 0, 1) == '/') $index = substr($index, 1);
        if (substr($index, -1) == '/') $index = substr($index, 0, strlen($index) - 1);
        //print "\n<br/>xml-pages::page_part($index, $pageset)";
        if (($this->source_part_nde("/pages/pageset[$pageset_check]/pagedef[@loc='$index']"))  != null) {
            $subpageset = $this->source_part_get("/pages/pageset[$pageset_check]/pagedef[@loc='$index']/@pageset");
            // print "\n<br/>xml-pages::page_part - exact match $index (pageset=$pageset)";

            if ($subpageset != null) {
                $pageset = $subpageset;
                return $this->page_part_element("", $subpageset);
            }
            return $this->source_part_nde("/pages/pageset[$pageset_check]/pagedef[@loc='$index']");
        }
        $path = $index;
        $rest = "";
        while (true) {
            if ($path == '') break;
            $x = strrpos($path, '/');
            if (($x = strrpos($path, '/')) == false) {
                $rest = "$path/$rest";
                $path = '';
            } else {
                $rest = substr($path, $x + 1) . ($rest == "" ? "" : "/") . $rest;
                $path = substr($path, 0, $x);
            }
            // print "\n<br/>xml-pages::page_part - Searching path tree: path=$path, rest=$rest";
            // print "\n<br/>xml-pages::page_part - Searching: /pages/pageset[$pageset_check]/pagedef[@loc='$path']/@pageset";

            $subpageset = $this->source_part_get("/pages/pageset[$pageset_check]/pagedef[@loc='$path']/@pageset");
            if ($subpageset != null) {
                // print "\n<br/>xml-pages::page_part - subpath pageset $pageset";
                $pageset = $subpageset;
                $subset_result = $this->page_part_element($rest, $pageset);
                if ($subset_result != null) return $subset_result;
                // print "\n<br/>xml-pages::page_part - subpath didn't find.  No 404 handler provided.";
                break;
            }
        }

        if ($index == "") {
            // print "\n<br/>xml-pages::page_part - Checking default on /pages/pageset[$pageset_check]/pagedef[@default]/@loc";
            if ($this->source_part_get("/pages/pageset[$pageset_check]/pagedef[@default]/@loc") != '') {
                // print "\n<br/>xml-pages::page_part - default match";
                return $this->source_part_nde("/pages/pageset[$pageset_check]/pagedef[@default]");
            }
        } else {
            // print "\n<br/>xml-pages::page_part - Checking 404 on /pages/pageset[$pageset_check]/pagedef[@default404]/@loc";
            if ($this->source_part_get("/pages/pageset[$pageset_check]/pagedef[@default404]/@loc") != '') {
                // print "\n<br/>xml-pages::page_part - 404 match";
                return $this->source_part_nde("/pages/pageset[$pageset_check]/pagedef[@default404]");
            }
        }

        // print "\n<br/>xml-pages::page_part - NO MATCH";
        return null;
    }

    function parse_special($pagedef) {
        // 301 Moved Permanently
        // 302 Found
        // 303 See Other
        // 307 Temporary Redirect
        
        if (($url = $pagedef->get("/@redirect")) != '') {
            $type = $pagedef->get("/@redirect-type");
            if ($type == '') $type = 301;
            die(header("Location: $url",TRUE,307));
        }
    }

    function get_page($index)
    {
        $pagedef = $this->page_part($index);
        $this->parse_special($pagedef);
        $page = page_render::make_page($pagedef);
        $result = xml_file::make_tidy_string($page->saveXML());
        return $result;
    }
}
