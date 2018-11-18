<?php

class CacheSystem {
    public static function get($id) {
        if (!file_exists('cache/' . md5($id)))
            return array(false, null);
        return array(true, unserialize(file_get_contents('cache/' . md5($id))));
    }

    public static function store($id, $data) {
        if (!file_exists('cache'))
            mkdir('cache');
        file_put_contents('cache/' . md5($id), serialize($data));
    }
}
