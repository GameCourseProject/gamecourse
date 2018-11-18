<?php
class FlintstoneWrapper extends Wrapper {
    public function __construct($db) {
        $this->db = $db;
    }

    public function &getValue() {
        $v = $this->db->getValue();
        return $v;
    }

    public function setValue($value) {
        if (is_null($value) || !is_array($value))
            throw new Exception("Unsupported!");
        foreach ($value as $k => $v)
            $this->db->set($k, $v);
    }

    public function setValueRef(&$value) {
        throw new Exception("Unsupported!");
    }

    public function isNull() {
        throw new Exception("Unsupported!");
    }

    public function isArray() {
        throw new Exception("Unsupported!");
    }

    public function size() {
        return count($this->db->getKeys());
    }

    public function hasKey($key) {
        return in_array($key, $this->db->getKeys());
    }

    public function has($value) {
        throw new Exception("Unsupported!");
    }

    public function set($key, $value, $returnRaw = false) {
        $this->db->set($key, $value);
        if ($returnRaw)
            throw new Exception("Unsupported!");
        return $this;
    }

    public function setRef($key, &$value, $returnRaw = false) {
        throw new Exception("Unsupported!");
    }

    public function push($value, $returnRaw = false) {
        throw new Exception("Unsupported!");
    }

    public function pushRef(&$value, $returnRaw = false) {
        throw new Exception("Unsupported!");
    }

    public function &get($key, $default = null) {
        $v = $this->db->get($key);
        if ($v === false)
            return $default;
        return $v;
    }

    public function delete($key, $returnRaw = false) {
        $this->db->delete($key);
        if ($returnRaw)
            throw new Exception("Unsupported!");
        return $this;
    }

    public function getWrapped($key, $create = true, $setParent = false) {
        return parent::getWrapped($key, true, true);
    }

    public function setValueComplex($complexKey, $value) {
        throw new Exception("Unsupported!");
    }

    public function getWrappedComplex($complexKey, $create = true, $setParent = false) {
        return parent::getWrappedComplex($complexKey, true, true);
    }

    public function filter($callback) {
        throw new Exception("Unsupported!");
    }

    public function map($callback) {
        throw new Exception("Unsupported!");
    }

    public function sort($callback) {
        throw new Exception("Unsupported!");
    }

    private $db;
}
