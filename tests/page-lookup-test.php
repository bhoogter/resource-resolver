<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class page_lookup extends TestCase
{
	private const PAGES_XML = __DIR__ . "/resources/pages.xml";
	protected static $subject = null;

	public static function setUpBeforeClass(): void
	{
		self::$subject = new xml_serve(self::PAGES_XML, __DIR__ . "/resources");
	}

	public function lookupPageId($path)
	{
		return self::$subject->page_part($path)->get("/pagedef/@loc");
	}

	public function testDefaultLookup(): void
	{
		$this->assertEquals("home", $this->lookupPageId("/"));
		$this->assertEquals("home", $this->lookupPageId(""));
		$this->assertEquals("about", $this->lookupPageId("about"));
		$this->assertEquals("about", $this->lookupPageId("/about"));
		$this->assertEquals("about", $this->lookupPageId("/about/"));
		$this->assertEquals("contact", $this->lookupPageId("contact"));
		$this->assertEquals("404", $this->lookupPageId("fake"));
	}

	public function testSubdef1Loads(): void
	{
		$this->assertEquals("sub1home", $this->lookupPageId("sub"));
		$this->assertEquals("sub1home", $this->lookupPageId("sub/sub1home"));
		$this->assertEquals("sub1home", $this->lookupPageId("sub/sub1home/"));
		$this->assertEquals("info", $this->lookupPageId("sub/info"));
		$this->assertEquals("info", $this->lookupPageId("sub/info/"));
		$this->assertEquals("content", $this->lookupPageId("sub/content"));
		$this->assertEquals("4041", $this->lookupPageId("sub/fake"));
	}

	public function testSubdef1DuplicatesLoads(): void
	{
		$this->assertEquals("sub1home", $this->lookupPageId("sub"));
		$this->assertEquals("info", $this->lookupPageId("sub/info"));
		$this->assertEquals("sub1home", $this->lookupPageId("sub/sub"));
		$this->assertEquals("info", $this->lookupPageId("sub/sub/info"));
		$this->assertEquals("sub1home", $this->lookupPageId("sub/sub/sub"));
		$this->assertEquals("info", $this->lookupPageId("sub/sub/sub/info"));
	}

	public function testSubdef2Loads(): void
	{
		$this->assertEquals("sub2home", $this->lookupPageId("sub2/"));
		$this->assertEquals("extra", $this->lookupPageId("sub2/extra"));
		$this->assertEquals("sub2home", $this->lookupPageId("sub2/sub2"));
		$this->assertEquals("extra", $this->lookupPageId("sub2/sub2/extra"));
		$this->assertEquals("404", $this->lookupPageId("sub2/fake"));

		$this->assertEquals("sub2/extra2", $this->lookupPageId("sub2/extra2"));
	}

	public function testSubdefDeepLookupExists(): void
	{
		$this->assertEquals("further/and/further/yet", $this->lookupPageId("/sub3/sub3/sub3/further/and/further/yet"));
		$this->assertEquals("sub3home", $this->lookupPageId("sub3/sub3/sub3/"));
	}

	public function testSubdef1cContent(): void
	{
		$this->assertEquals("content", $this->lookupPageId("/sub/content"));
		$this->assertEquals("content", $this->lookupPageId("/sub1b/content"));
		$this->assertEquals("content", $this->lookupPageId("/sub1c/content"));
	}

	public function testSub1Lev1Home(): void
	{
		$this->assertEquals("sub1home", $this->lookupPageId("/sub"));
		$this->assertEquals("sub1home", $this->lookupPageId("/sub/sub1home"));
		$this->assertEquals("sub1home", $this->lookupPageId("/sub/sub/"));
		$this->assertEquals("sub1home", $this->lookupPageId("/sub/sub/sub1home"));
		$this->assertEquals("sub1home", $this->lookupPageId("/sub/sub/sub"));
		$this->assertEquals("sub1home", $this->lookupPageId("/sub/sub/sub/sub1home"));
	}

	public function testSub2OnLongPathExtra(): void
	{
		$this->assertEquals("extra", $this->lookupPageId("some/long/path/somewhere/sub2/extra"));
	}

	public function testSub2Default(): void
	{
		$this->assertEquals("sub2home", $this->lookupPageId("sub2"));
		$this->assertEquals("again", $this->lookupPageId("sub2/again"));
		$this->assertEquals("sub2home", $this->lookupPageId("sub2/sub2"));
		$this->assertEquals("again", $this->lookupPageId("sub2/sub2/again"));
		$this->assertEquals("sub2/extra2", $this->lookupPageId("sub2/extra2"));
	}
}
