<?php
namespace MagicDB;

interface MagicInterface {
    public function set($key, $value);
    public function get($key);
    public function getValue();
    public function setValue($value);
    public function getType();
    public function getKeys();
    public function getSize();
}
