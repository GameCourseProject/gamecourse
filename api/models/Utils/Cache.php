<?php
namespace Utils;

use Exception;
use GameCourse\Core\Core;
use Opis\Closure\SerializableClosure;

/**
 * Holds functions to store and retrieve data from
 * cache - a set of files saved on the server.
 */
class Cache
{

    const TABLE_VIEWS_CACHE = "views_cache";

    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    // NOTE: the main cache uses files as a way to store data.
    //      This way, it ensures the cache persists even after
    //      the request is over.

    /**
     * Gets data from cache.
     * Returns null if cache doesn't exist.
     *
     * @param int|null $courseId,
     * @param string $cacheId
     * @return mixed|null
     */
    public static function get(?int $courseId, string $cacheId)
    {
        $cache = CACHE_FOLDER . "/" . (!is_null($courseId) ? "c$courseId/" : "") . $cacheId . ".txt";
        if (!file_exists($cache)) return null;
        return self::unserialize(file_get_contents($cache));
    }

    /**
     * Stores data in cache.
     *
     * @param int|null $courseId
     * @param string $cacheId
     * @param $data
     * @return void
     */
    public static function store(?int $courseId, string $cacheId, $data)
    {
        if (is_null($courseId) && !file_exists(CACHE_FOLDER))
            mkdir(CACHE_FOLDER, 0777, true);
        else if (!is_null($courseId) && !file_exists(CACHE_FOLDER . "/c$courseId"))
            mkdir(CACHE_FOLDER . "/c$courseId", 0777, true);

        $cache = CACHE_FOLDER . "/" . (!is_null($courseId) ? "c$courseId/" : "") . $cacheId . ".txt";
        file_put_contents($cache, self::serialize($data));
    }

    /**
     * Cleans whole cache or a specific cache if ID given.
     *
     * @param int|null $courseId
     * @param string|null $cacheId
     * @return void
     * @throws Exception
     */
    public static function clean(int $courseId = null, string $cacheId = null)
    {
        if (is_null($courseId) && is_null($cacheId) && file_exists(CACHE_FOLDER))
            Utils::deleteDirectory(CACHE_FOLDER);
        else if (!is_null($courseId) && is_null($cacheId) && file_exists(CACHE_FOLDER . "/c$courseId"))
            Utils::deleteDirectory(CACHE_FOLDER . "/c$courseId");
        else if (is_null($courseId) && !is_null($cacheId) && file_exists(CACHE_FOLDER . "/" . $cacheId . ".txt"))
            Utils::deleteFile(CACHE_FOLDER, $cacheId . ".txt");
        else if (!is_null($courseId) && !is_null($cacheId) && file_exists(CACHE_FOLDER . "/c$courseId/" . $cacheId . ".txt"))
            Utils::deleteFile(CACHE_FOLDER . "/c$courseId", $cacheId . ".txt");
    }


    /*** --------------------------------------------- ***/
    /*** ------------------- Views ------------------- ***/
    /*** --------------------------------------------- ***/

    // NOTE: the views cache uses a variable as a way to store data.
    //      This way, retrieving and storing data is much faster as
    //      there is no need to persist the cache after the request is over.

    private static $viewsCache = [];
    private static $viewsCacheInDatabase = [];

    /**
     * Gets data from views cache.
     *
     * @param string $cacheId
     * @return mixed|null
     */
    public static function getFromViewsCache(string $cacheId)
    {
        if (isset(self::$viewsCacheInDatabase[$cacheId])) {
            return self::$viewsCacheInDatabase[$cacheId];
        } else if (isset(self::$viewsCache[$cacheId])) {
            return self::$viewsCache[$cacheId];
        } else {
            return null;
        }
    }

    /**
     * Stores data in views cache.
     *
     * @param string $cacheId
     * @param $data
     * @return void
     */
    public static function storeInViewsCache(string $cacheId, $data)
    {
        self::$viewsCache[$cacheId] = $data;
    }

    public static function storeViewsInDatabase(int $pageId, int $userId = null) {
        if($userId === null) {
            $insertQuery = "INSERT INTO " . self::TABLE_VIEWS_CACHE . " (page_id, cache_key, cache_value) VALUES ";
        } else {
            $insertQuery = "INSERT INTO " . self::TABLE_VIEWS_CACHE . " (page_id, user_id, cache_key, cache_value) VALUES ";
        }

        $values = [];

        foreach (self::$viewsCache as $key => $value) {
            $compressedKey = base64_encode(gzcompress($key));
            $compressedValue = base64_encode(gzcompress($value));

            if($userId === null) {
                $values[] = "({$pageId},'{$compressedKey}','{$compressedValue}')";
            } else {
                $values[] = "({$pageId},{$userId},'{$compressedKey}','{$compressedValue}')";
            }
        }

        $insertQuery .= implode(", ", $values);

        Core::database()->executeQuery($insertQuery);
        self::$viewsCache = [];
        Core::dictionary()->cleanViews();
    }

    public static function loadFromDatabase(int $pageId, string $type, int $userId) {
        if ($type === "individual") {
            self::loadCache(["page_id" => $pageId, "user_id" => $userId]);
        } else {
            self::loadCache(["page_id" => $pageId]);
        }
    }

    private static function loadCache(array $where) {
        $results = Core::database()->selectMultiple(self::TABLE_VIEWS_CACHE, $where, "cache_key, cache_value");
        foreach ($results as $res) {
            $key = gzuncompress(base64_decode($res["cache_key"]));
            $value = gzuncompress(base64_decode($res["cache_value"]));
            self::$viewsCacheInDatabase[$key] = $value;
        }
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Helpers ------------------ ***/
    /*** --------------------------------------------- ***/

    private static function serialize($data): string
    {
        self::wrapFunctions($data);
        return serialize($data);
    }

    private static function wrapFunctions(&$data)
    {
        if (is_callable($data)) $data = new SerializableClosure($data);
        else if (is_array($data)) {
            foreach ($data as &$item) {
                self::wrapFunctions($item);
            }
        }
    }

    private static function unserialize($data)
    {
        $data = unserialize($data);
        self::unwrapFunctions($data);
        return $data;
    }

    private static function unwrapFunctions(&$data)
    {
        if (is_object($data)) $data = $data->getClosure();
        else if (is_array($data)) {
            foreach ($data as &$item) {
                self::unwrapFunctions($item);
            }
        }
    }
}
