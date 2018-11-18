<?php
namespace SmartBoards;

class DataRetrieverContinuation {
    private $value;
    private $cont;

    public function __construct($value, $cont) {
        $this->value = $value;
        $this->cont = $cont;
    }

    public function getValue($wrapped = false) {
        if ($wrapped)
            return $this->value;
        return $this->value->getValue();
    }

    public function getKeys() {
        return $this->value->getKeys();
    }

    public function execute($path, $context = array()) {
        $cont = ($this->cont);
        return $cont($path, $context);
    }

    public function followKey($key) {
        $cont = ($this->cont);
        return $cont(null, null, $key);
    }

    private static function arrayContinuation($array) {
        return function($path, $context, $key = null) use ($array) {
            $value = array_reduce(explode('.', $path), function($arr, $path) {
                return $arr[$path];
            }, $array);
            return static::buildForArray($value);
        };
    }

    public static function buildForArray($arr) {
        return new DataRetrieverContinuation(new \ValueWrapper($arr), static::arrayContinuation($arr));
    }
}
