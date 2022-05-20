<?php
namespace Utils;

use Exception;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class CacheTest extends TestCase
{
    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass();
    }

    protected function tearDown(): void
    {
        TestingUtils::cleanFileStructure();
    }

    protected function onNotSuccessfulTest(Throwable $t): void
    {
        $this->tearDown();
        parent::onNotSuccessfulTest($t);
    }

    public static function tearDownAfterClass(): void
    {
        TestingUtils::tearDownAfterClass();
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Data Providers ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public function cacheProvider(): array
    {
        return [
            "integer" => [1],
            "zero" => [0],
            "string" => ["abcdef"],
            "empty string" => [""],
            "bool true" => [true],
            "bool false" => [false],
            "simple array" => [[1, 2, 3]],
            "complex array" => [[1, "abcdef", true]],
            "associative array" => [["a" => 1, "b" => 2, "c" => 3]],
            "function" => [function (...$args) { return count($args); }],
            "nested functions" => ["a" => ["1" => function (...$args) { return count($args); }]],
            "null" => [null]
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     * @dataProvider cacheProvider
     */
    public function get($var)
    {
        $cacheId = "test";
        Cache::store($cacheId, $var);
        $this->assertEquals($var, Cache::get($cacheId));
    }

    /**
     * @test
     */
    public function getCacheDoesntExist()
    {
        $this->assertNull(Cache::get("test"));
    }


    /**
     * @test
     * @dataProvider cacheProvider
     */
    public function store($data)
    {
        $cacheId = "test";
        Cache::store($cacheId, $data);
        $this->assertEquals($data, Cache::get($cacheId));
    }

    /**
     * @test
     */
    public function storeMultiple()
    {
        Cache::store("test1", 1);
        Cache::store("test2", 2);
        $this->assertEquals(1, Cache::get("test1"));
        $this->assertEquals(2, Cache::get("test2"));
    }


    /**
     * @test
     * @throws Exception
     */
    public function cleanWholeCache()
    {
        $cacheId = "test";
        Cache::store($cacheId, 1);
        Cache::clean();
        $this->assertFalse(file_exists(CACHE_FOLDER));
    }

    /**
     * @test
     * @throws Exception
     */
    public function cleanSpecificCache()
    {
        Cache::store("test1", 1);
        Cache::store("test2", 2);
        Cache::clean("test1");
        $this->assertNull(Cache::get("test1"));
        $this->assertEquals(2, Cache::get("test2"));
    }
}
