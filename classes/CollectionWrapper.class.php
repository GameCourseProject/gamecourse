<?php
class CollectionWrapper extends Wrapper {
    private $collection;
    private $collectionValue = null;

    public function __construct(array $collection) {
        $this->collection = $collection;
    }

    public function &getValue() {
        if ($this->collectionValue !== null)
            return $this->collectionValue;
        $this->collectionValue = array();
        foreach ($this->collection as $key => $valueWrapped)
            $this->collectionValue[$key] = $valueWrapped->getValue();
        return $this->collectionValue;
    }

    public function setValue($value) {
        throw new Exception("Collections are read-only.");
    }

    public function setValueRef(&$value) {
        throw new Exception("Collections are read-only.");
    }

    public function getKeys() {
        return array_keys($this->collection);
    }

    public function isNull() {
        return false;
    }

    public function isArray() {
        return true;
    }

    public function size() {
        return count($this->collection);
    }

    public function hasKey($key) {
        return array_key_exists($key, $this->collection);
    }

    public function has($value) {
        static::unwrapValue($value);
        $data = &$this->getValue();
        if ($data != null && is_array($data))
            return in_array($value, $data);
        return false;
    }

    public function set($key, $value, $returnRaw = false) {
        throw new Exception("Collections are read-only.");
    }

    public function setRef($key, &$value, $returnRaw = false) {
        throw new Exception("Collections are read-only.");
    }

    public function push($value, $returnRaw = false) {
        throw new Exception("Collections are read-only.");
    }

    public function pushRef(&$value, $returnRaw = false) {
        throw new Exception("Collections are read-only.");
    }

    public function &get($key, $default = null) {
        if ($this->collectionValue != null) {
            return $this->collectionValue[$key];
        }

        static::unwrapValue($default);
        if (!$this->hasKey($key))
            return $default;
        return $this->collection[$key]->getValue();
    }

    public function delete($key, $returnRaw = false) {
        throw new Exception("Collections are read-only.");
    }

    public function getWrapped($key, $create = true, $setParent = false) {
        if ($this->hasKey($key))
            return $this->collection[$key];
        throw new Exception("Collections are read-only.");
    }

    public function setValueComplex($complexKey, $value) {
        throw new Exception("Collections are read-only.");
    }

    public function getIterator() {
        foreach ($this->collection as $key => $valueWrapped)
            yield $key => $valueWrapped;
    }
}
