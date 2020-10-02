<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class phars_test extends TestCase
{
    private const CONTENT_FOLDER = __DIR__ . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "content";
    private const TEMPLATES_FOLDER = self::CONTENT_FOLDER . DIRECTORY_SEPARATOR . "templates";

    private static $subject;

	public static function setUpBeforeClass(): void
	{
        require_once(__DIR__ . "/test_logger.php");
        php_logger::$on = false;
		resource_resolver::instance()->init(self::CONTENT_FOLDER);
        self::$subject = resource_resolver::instance();
        php_logger::$on = false;
    }
    
    public function testAvailablePhars() {
        $result = self::$subject->available_phars(self::TEMPLATES_FOLDER);
        print_r($result);
        $this->assertTrue(0 < count($result));
        $this->assertTrue(false !== strpos($result[0], __DIR__));
        $this->assertTrue(false !== strpos($result[0], self::TEMPLATES_FOLDER));
        $this->assertTrue(false !== strpos($result[0], "test-phar-1.phar"));
    }
    
    public function testResolvePharFile() {
        php_logger::$on = true;
        $result = self::$subject->resolve_files("template.xml", "template", "test-phar-1");
        $this->assertTrue(false !== strpos($result[0], "test-phar-1.phar"));
        $this->assertTrue(false !== strpos($result[0], "template.xml"));
        print "\n--------------------\n";
        print_r($result);
        
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
        $resultFile = self::$subject->resolve_file("logo-*.jpg", "template", "test-phar-1");
        $result = self::$subject->resolve_ref("logo-*.jpg", "template", "test-phar-1");
        // php_logger::log("resc_root=".self::$subject->resource_root);
        // php_logger::log("http_root=".self::$subject->http_root);
        // php_logger::log("result=$result");
        $this->assertEquals("phar://" . str_replace("\\", "/", __DIR__) . "/resources/content/templates/test-phar-1.phar/src/logo-1.jpg", $resultFile);
        $this->assertEquals("/content/templates/test-phar-1.phar/src/logo-1.jpg", $result);
    }

    public function testResolvePharImageRefAlreadyRef() {
        php_logger::$on = true;
        $result = self::$subject->resolve_ref("/content/templates/test-phar-1.phar/src/logo-1.jpg", "template", "test-phar-1");
        // php_logger::log("resc_root=".self::$subject->resource_root);
        // php_logger::log("http_root=".self::$subject->http_root);
        // php_logger::log("result=$result");
        $this->assertEquals("/content/templates/test-phar-1.phar/src/logo-1.jpg", $result);
    }

    // public function testResolvePharImageRefAlreadyRefMulti() {
    //     php_logger::$on = true;
    //     $result = self::$subject->resolve_ref("/content/templates/test-phar-1.phar/src/logo-*.jpg", "template", "test-phar-1");
    //     // php_logger::log("resc_root=".self::$subject->resource_root);
    //     // php_logger::log("http_root=".self::$subject->http_root);
    //     // php_logger::log("result=$result");
    //     $this->assertEquals("/content/templates/test-phar-1.phar/src/logo-1.jpg", $result);
    // }
}
