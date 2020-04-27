<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class phars_test extends TestCase
{
    private static $subject;

	public static function setUpBeforeClass(): void
	{
        require_once(__DIR__ . "/test_logger.php");
        php_logger::$on = false;
		resource_resolver::instance()->init(__DIR__ . "/resources/content");
        self::$subject = resource_resolver::instance();
    }
    
    public function testAvailablePhars() {
        $result = self::$subject->available_phars(__DIR__ . "/resources/content/templates");
        $this->assertTrue(0 < count($result));
    }
    
    public function testResolvePharFile() {
        // php_logger::$on = true;
        $result = self::$subject->resolve_files("template.xml", "template", "test-phar-1");
        $this->assertTrue(false !== strpos($result[0], "test-phar-1.phar"));
        $this->assertTrue(false !== strpos($result[0], "template.xml"));
        // print_r($result);
        
        $result2 = self::$subject->resolve_files("class-test-phar-*.php", "template", "test-phar-1");
        // print_r($result2);
        $this->assertEquals(3, count($result2));
    }
    
    public function testResolvePharImageFile() {
        // php_logger::$on = true;
        $result = self::$subject->resolve_file("logo-*.jpg", "template", "test-phar-1");
        // print_r($result);
        $this->assertTrue(is_string($result));
        $this->assertTrue(false !== strpos($result, "logo-1.jpg"));
    }

    public function testResolvePharImageRef() {
        // php_logger::$on = true;
        $result = self::$subject->resolve_ref("logo-*.jpg", "template", "test-phar-1");
        // php_logger::log("resc_root=".self::$subject->resource_root);
        // php_logger::log("http_root=".self::$subject->http_root);
        // php_logger::log("result=$result");
        $this->assertEquals("/content/templates/test-phar-1.phar/src/logo-1.jpg", $result);
    }
}