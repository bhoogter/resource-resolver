<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class phars_test extends TestCase
{
    const TESTCASE1 = "/content/templates/test-phar-1.phar/src/logo-1.jpg";
    public static $subject;

	public static function setUpBeforeClass(): void
	{
        require_once(__DIR__ . "/test_logger.php");
        php_logger::$on = false;
		resource_resolver::instance()->init(__DIR__ . "/resources/content");
        self::$subject = resource_resolver::instance();
    }

    public function test_is_phurl()
    {
        $this->assertTrue(self::$subject->is_phurl(self::TESTCASE1));
    }

    public function test_phurl_type()
    {
        $this->assertEquals("jpg", self::$subject->phurl_type(self::TESTCASE1));
    }

    public function test_phurl_path()
    {
        $this->assertEquals("/src/logo-1.jpg", self::$subject->phurl_path(self::TESTCASE1));
    }

    public function test_phurl_file()
    {
        $this->assertEquals(realpath(__DIR__ . "/resources/content/templates/test-phar-1.phar"), self::$subject->phurl_file(self::TESTCASE1));
    }

    public function test_is_phurl_type()
    {
        $this->assertTrue(self::$subject->is_phurl_type("jpg"));
        $this->assertTrue(self::$subject->is_phurl_type("ico"));
        $this->assertTrue(self::$subject->is_phurl_type("txt"));

        $this->assertFalse(self::$subject->is_phurl_type("php"));
        $this->assertFalse(self::$subject->is_phurl_type("inc"));
    }

    public function test_phurl(): void 
    {
        // php_logger::$on = true;
        ob_start();
        $result = self::$subject->phurl(self::TESTCASE1);
        $res = ob_get_clean();
        $size = strlen($res);
        $fsize = filesize(self::$subject->phurl_file(self::TESTCASE1));
        // print "\n------------------------\nprint "size=$size, fsize=$fsize";

        $this->assertTrue($result);
        $this->assertTrue($size > 45000);
    }
}
