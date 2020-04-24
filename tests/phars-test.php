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
		resource_resolver::instance()->init(
            __DIR__ . "/resources/content",
			realpath(__DIR__ . "/resources")
		);
        self::$subject = resource_resolver::instance();
    }
    
    public function testAvailablePhars() {
        $result = self::$subject->available_phars(__DIR__ . "/resources/content/templates");
        $this->assertTrue(0 < count($result));
    }
    
    public function testResolvePharFile() {
        $result = self::$subject->resolve_files("template.xml", "template", "test-phar-1");
        $this->assertTrue(false !== strpos($result[0], "test-phar-1.phar"));
        $this->assertTrue(false !== strpos($result[0], "template.xml"));
        // print_r($result);
        
        $result2 = self::$subject->resolve_files("class-test-phar-*.php", "template", "test-phar-1");
        // print_r($result2);
        $this->assertEquals(3, count($result2));
    }
    
    public function testResolvePharImage() {
        php_logger::$on = true;
        $this->assertTrue(true);

    }

}
