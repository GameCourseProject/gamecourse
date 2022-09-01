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

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass();
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        TestingUtils::cleanFileStructure();
    }

    protected function onNotSuccessfulTest(Throwable $t): void
    {
        $this->tearDown();
        parent::onNotSuccessfulTest($t);
    }

    /**
     * @throws Exception
     */
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

    // Getting

    /**
     * @test
     * @dataProvider cacheProvider
     */
    public function get($var)
    {
        // No course
        $cacheId = "test";
        Cache::store(null, $cacheId, $var);
        $this->assertEquals($var, Cache::get(null, $cacheId));

        // With course
        $courseId = 100;
        Cache::store($courseId, $cacheId, $var);
        $this->assertEquals($var, Cache::get($courseId, $cacheId));
    }

    /**
     * @test
     */
    public function getCacheDoesntExist()
    {
        // No Course
        $this->assertNull(Cache::get(null, "test"));

        // With course
        $this->assertNull(Cache::get(100, "test"));
    }


    // Storing

    /**
     * @test
     * @dataProvider cacheProvider
     */
    public function store($data)
    {
        // No course
        $cacheId = "test";
        Cache::store(null, $cacheId, $data);
        $this->assertEquals($data, Cache::get(null, $cacheId));

        // With course
        $courseId = 100;
        Cache::store($courseId, $cacheId, $data);
        $this->assertEquals($data, Cache::get($courseId, $cacheId));
    }

    /**
     * @test
     */
    public function storeMultiple()
    {
        // No course
        Cache::store(null, "test1", 1);
        Cache::store(null, "test2", 2);
        $this->assertEquals(1, Cache::get(null, "test1"));
        $this->assertEquals(2, Cache::get(null, "test2"));

        // With course
        $courseId = 100;
        Cache::store($courseId, "test1", 1);
        Cache::store($courseId, "test2", 2);
        $this->assertEquals(1, Cache::get($courseId, "test1"));
        $this->assertEquals(2, Cache::get($courseId, "test2"));
    }


    // Cleaning

    /**
     * @test
     * @throws Exception
     */
    public function cleanWholeCache()
    {
        Cache::store(null, "test", 1);
        Cache::store(100, "test", 1);
        Cache::clean();
        $this->assertFalse(file_exists(CACHE_FOLDER));
    }

    /**
     * @test
     * @throws Exception
     */
    public function cleanSpecificCache()
    {
        Cache::store(null, "test1", 1);
        Cache::store(null, "test2", 2);
        Cache::store(100, "test3", 3);
        Cache::clean(null, "test1");
        $this->assertNull(Cache::get(null, "test1"));
        $this->assertEquals(2, Cache::get(null, "test2"));
        $this->assertEquals(3, Cache::get(100, "test3"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function cleanCourseCache()
    {
        Cache::store(null, "test1", 1);
        Cache::store(null, "test2", 2);
        Cache::store(100, "test3", 3);
        Cache::clean(100);
        $this->assertEquals(1, Cache::get(null, "test1"));
        $this->assertEquals(2, Cache::get(null, "test2"));
        $this->assertNull(Cache::get(100, "test3"));
        $this->assertFalse(file_exists(CACHE_FOLDER . "/100"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function cleanSpecificCourseCache()
    {
        Cache::store(null, "test1", 1);
        Cache::store(null, "test2", 2);
        Cache::store(100, "test3", 3);
        Cache::store(100, "test4", 4);
        Cache::clean(100, "test3");
        $this->assertEquals(1, Cache::get(null, "test1"));
        $this->assertEquals(2, Cache::get(null, "test2"));
        $this->assertNull(Cache::get(100, "test3"));
        $this->assertEquals(4, Cache::get(100, "test4"));
    }
}
