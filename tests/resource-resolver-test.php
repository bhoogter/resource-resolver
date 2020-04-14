<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class resource_resolver_tests extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		resource_resolver::instance()->init(
			__DIR__ . "/resources/content",
			realpath(__DIR__ . "/resources")
		);
	}

	private function file($spec, $types = [], $mappings = [])
	{
		return resource_resolver::instance()->resolve_file($spec, $types, $mappings);
	}

	private function files($spec, $types = [], $mappings = [])
	{
		return resource_resolver::instance()->resolve_files($spec, $types, $mappings);
	}

	private function ref($spec, $types = [], $mappings = [])
	{
		return resource_resolver::instance()->resolve_ref($spec, $types, $mappings);
	}

	public function fileFound($spec, $types = [], $mappings = [], $containing = "")
	{
		$res = $this->file($spec, $types, $mappings);
		if ($res == null) {
			print "\nResource not found: [$spec]";
			return false;
		}

		if ($containing != "" && strpos(file_get_contents($res), $containing) === false) {
			print "\nResource did not contain '$containing'\n In: $res";
			return false;
		}

		$type_string = is_array($types) ? implode(', ', $types) : $types;
		// print "\n fileFound($spec, $type_string): " . substr($res, -30);
		return substr($res, -strlen($spec)) == $spec;
	}

	public function filesFound($spec, $types = [], $mappings = [])
	{
		$res = $this->files($spec, $types, $mappings);
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
		$this->assertTrue($this->fileFound("template.xml", "template", "main", "name='Forest'"));
		$this->assertTrue($this->fileFound("style.css", "template", "main", "main template style"));
		$this->assertTrue($this->fileFound("links.html", "template", "main", "nav-main"));
		$this->assertFalse($this->fileFound("links.html", "template", "main", "not found"));
	}

	public function testResolveTemplateXmlSubDir(): void
	{
		$this->assertTrue($this->fileFound("logo-1.jpg", "template", "main"));
		$this->assertEquals(7, $this->filesFound("logo-*.jpg", "template", "main"));
	}

	public function testResolveTemplateRef(): void
	{
		$this->assertEquals(
			"/content/templates/main/style.css",
			$this->ref("style.css", "template", "main")
		);
	}
}
