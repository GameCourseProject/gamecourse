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
     * @return mixed|null
     */
    public static function get(string $cacheId)
    {
        $cache = CACHE_FOLDER . "/" . $cacheId . ".txt";
        if (!file_exists($cache)) return null;
        return self::unserialize(file_get_contents($cache));
    }

    /**
     * Stores data in cache.
     *
     * @return void
     */
    public static function store(string $cacheId, $data)
    {
        if (!file_exists(CACHE_FOLDER)) mkdir(CACHE_FOLDER);
        file_put_contents(CACHE_FOLDER . "/" . $cacheId . ".txt", self::serialize($data));
    }

    /**
     * Cleans whole cache or a specific cache if ID given.
     *
     * @param string|null $cacheId
     * @return void
     * @throws Exception
     */
    public static function clean(string $cacheId = null)
    {
        if (is_null($cacheId)) Utils::deleteDirectory(CACHE_FOLDER);
        else unlink(CACHE_FOLDER . "/" . $cacheId . ".txt");
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
