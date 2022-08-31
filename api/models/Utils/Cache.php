<?php
namespace Utils;

use Exception;
use Opis\Closure\SerializableClosure;

/**
 * Holds functions to store and retrieve data from
 * cache - a set of files saved on the server.
 */
class Cache
{
    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/


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
        $cache = CACHE_FOLDER . "/" . (!is_null($courseId) ? $courseId . "/" : "") . $cacheId . ".txt";
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
        else if (!is_null($courseId) && !file_exists(CACHE_FOLDER . "/" . $courseId))
            mkdir(CACHE_FOLDER . "/" . $courseId, 0777, true);

        $cache = CACHE_FOLDER . "/" . (!is_null($courseId) ? $courseId . "/" : "") . $cacheId . ".txt";
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
        else if (!is_null($courseId) && is_null($cacheId) && file_exists(CACHE_FOLDER . "/" . $courseId))
            Utils::deleteDirectory(CACHE_FOLDER . "/" . $courseId);
        else if (is_null($courseId) && !is_null($cacheId) && file_exists(CACHE_FOLDER . "/" . $cacheId . ".txt"))
            Utils::deleteFile(CACHE_FOLDER, $cacheId . ".txt");
        else if (!is_null($courseId) && !is_null($cacheId) && file_exists(CACHE_FOLDER . "/" . $courseId . "/" . $cacheId . ".txt"))
            Utils::deleteFile(CACHE_FOLDER . "/" . $courseId, $cacheId . ".txt");
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
