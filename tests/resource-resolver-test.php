<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class resource_resolver_tests extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once(__DIR__ . "/test_logger.php");
        php_logger::$on = false;
        resource_resolver::instance()->init(
            __DIR__ . "/resources/content",
            realpath(__DIR__ . "/resources")
        );
    }

    private function file($spec, $types = [], $mappings = [], $subfolders = [])
    {
        return resource_resolver::instance()->resolve_file($spec, $types, $mappings, $subfolders);
    }

    private function files($spec, $types = [], $mappings = [], $subfolders = [])
    {
        return resource_resolver::instance()->resolve_files($spec, $types, $mappings, $subfolders);
    }

    private function ref($spec, $types = [], $mappings = [], $subfolders = [])
    {
        return resource_resolver::instance()->resolve_ref($spec, $types, $mappings, $subfolders);
    }

    public function fileFound($spec, $types = [], $mappings = [], $subfolders = [], $containing = "")
    {
        $res = $this->file($spec, $types, $mappings, $subfolders);
        if ($res == null) {
            print "\nResource not found: [$spec]";
            return false;
        }

        if ($containing != "" && strpos(file_get_contents($res), $containing) === false) {
            print "\nResource did not contain '$containing'\n In: $res";
            return false;
        }

        $type_string = is_array($types) ? implode(', ', $types) : $types;
        // print "\n fileFound($spec, $type_string): " . $res;
        return substr($res, -strlen($spec)) == $spec;
    }

    public function filesFound($spec, $types = [], $mappings = [], $subfolders = [])
    {
        $res = $this->files($spec, $types, $mappings, $subfolders);
        return count($res);
    }

    public function testResolveHtmlFile(): void
    {
        $this->assertTrue($this->fileFound("main-content.html"));
        $this->assertTrue($this->fileFound("contact-content.html"));
        $this->assertTrue($this->fileFound("about-content.html"));
        $this->assertFalse($this->fileFound("does-not-exist.html"));
    }

    public function testResolveTemplateXml(): void
    {
        $this->assertTrue($this->fileFound("template.xml", "template", "main", [], "name='Forest'"));
        $this->assertTrue($this->fileFound("style.css", "template", "main", [], "main template style"));
        $this->assertTrue($this->fileFound("links.html", "template", "main", [], "nav-main"));
        $this->assertFalse($this->fileFound("links.html", "template", "main", [], "not found"));
    }

    public function testResolveTemplateXmlSubDir(): void
    {
        $this->assertTrue($this->fileFound("logo-1.jpg", "template", "main", ['images']));
        $this->assertEquals(7, $this->filesFound("logo-*.jpg", "template", "main", ['images']));
    }

    public function testResolveTemplateRef(): void
    {
        // print("\nref=". $this->ref("style.css", "template", "main"));
        $this->assertEquals(
            "/content/templates/main/style.css",
            $this->ref("style.css", "template", "main")
        );
    }

    public function testRootedPath(): void
    {
        $path = "/content/templates/main/images/logo-1.jpg";
        $this->assertEquals(
            $path,
            $this->ref($path)
        );
    }

    public function testRootedPattern(): void
    {
        $path = "/content/templates/main/images/logo-*.jpg";
        $this->assertEquals(
            7,
            count($this->files($path))
        );
    }

    public function testContentType(): void
    {
        $this->assertEquals('text/javascript', resource_resolver::instance()->content_type('js'));
        $this->assertEquals('text/css', resource_resolver::instance()->content_type('css'));
        $this->assertEquals('application/octet-stream', resource_resolver::instance()->content_type('unknown'));
    }
}
