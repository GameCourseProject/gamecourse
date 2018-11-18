<?php

abstract class Wrapper implements IteratorAggregate {

    public abstract function &getValue();
    public abstract function setValue($value);
    public abstract function setValueRef(&$value);

    public function getKeys() {
        return array_keys($this->getValue());
    }

    public function isNull() {
        return $this->getValue() == null;
    }

    public function isArray() {
        $value = $this->getValue();
        return $value != null && is_array($value);
    }

    public function size() {
        return count($this->getValue());
    }

    public function hasKey($key) {
        $data = &$this->getValue();
        if ($data != null && is_array($data))
            return array_key_exists($key, $data);
        return false;
    }

    public function has($value) {
        static::unwrapValue($value);
        $data = &$this->getValue();
        if ($data != null && is_array($data))
            return in_array($value, $data);
        return false;
    }

    public function set($key, $value, $returnRaw = false) {
        static::unwrapValue($value);
        $data = &$this->getValue();
        if ($data === null || !is_array($data)) {
            $data = array();
            $data[$key] = $value;
            $this->setValue($data);
        } else
            $data[$key] = $value;
        if ($returnRaw)
            return $data;
        return $this;
    }

    public function setRef($key, &$value, $returnRaw = false) {
        static::unwrapValue($value);
        $data = &$this->getValue();
        if ($data === null || !is_array($data)) {
            $data = array();
            $data[$key] = &$value;
            $this->setValue($data);
        } else
            $data[$key] = &$value;
        if ($returnRaw)
            return $data;
        return $this;
    }

    public function push($value, $returnRaw = false) {
        static::unwrapValue($value);
        $data = &$this->getValue();
        if ($data == null || !is_array($data)) {
            $data = array();
            $data[] = $value;
            $this->setValue($data);
        } else
            $data[] = $value;
        if ($returnRaw)
            return $data;
        return $this;
    }

    public function pushRef(&$value, $returnRaw = false) {
        static::unwrapValue($value);
        $data = &$this->getValue();
        if ($data == null || !is_array($data)) {
            $data = array();
            $data[] = &$value;
            $this->setValue($data);
        } else
            $data[] = &$value;
        if ($returnRaw)
            return $data;
        return $this;
    }

    public function &get($key, $default = null) {
        static::unwrapValue($default);
        $data = &$this->getValue();
        if ($data == null || !array_key_exists($key, $data))
            return $default;
        return $data[$key];
    }

    public function delete($key, $returnRaw = false) {
        $data = &$this->getValue();
        if (is_array($data) && array_key_exists($key, $data))
            unset($data[$key]);
        if ($returnRaw)
            return $data;
        return $this;
    }

    public function getWrapped($key, $create = true, $setParent = false) {
        if ($create || $this->getValue() != null)
            return new DataWrapper($this, $key, $setParent);
        return null;
    }

    public function setValueComplex($complexKey, $value) {
        return $this->getWrappedComplex($complexKey)->setValue($value);
    }

    public function getWrappedComplex($complexKey, $create = true, $setParent = false) {
        $keys = explode('.', $complexKey);
        return array_reduce($keys, function($obj, $key) use ($create, $setParent) {
            return is_null($obj) ? $obj : $obj->getWrapped($key, $create, $setParent);
        }, $this);
    }

    public function filter($callback) {
        $keys = $this->getKeys();
        $values = array();
        foreach ($keys as $key) {
            $wrapper = $this->getWrapped($key);
            if ($callback($key, $wrapper)) {
                $values[$key] = $wrapper;
            }
        }

        return new \CollectionWrapper($values);
    }

    public function map($callback) {
        $toMap = $this->getKeys();

        $values = array();
        foreach($toMap as $key) {
            $mappedValue = $callback($key, $this->getWrapped($key));
            $values[$key] = static::wrapValue($mappedValue);
        }
        return new \CollectionWrapper($values);
    }

    public function sort($callback) {
        $data = &$this->getValue();
        if ($data != null && is_array($data))
            $toSort = $data;
        else
            $toSort = array($data);

        usort($toSort, function($v1, $v2) use ($callback) {
            return $callback(new ValueWrapperRef($v1), new ValueWrapperRef($v2));
        });

        return new ValueWrapper($toSort);
    }

    protected static function unwrapValue(&$value) {
        if ($value != null && is_object($value) && is_subclass_of($value, 'Wrapper'))
            $value = $value->getValue();
    }

    protected static function wrapValue($value) {
        if (!is_subclass_of($value, 'Wrapper'))
            return new ValueWrapper($value);
        return $value;
    }

    /**
     * Modifying the value of the current wrapped object while iterating will result in undefined behaviour.
     */
    public function getIterator() {
        $keys = $this->getKeys();
        if ($keys != null && is_array($keys)) {
            foreach($keys as $key)
                yield $key => (new DataWrapper($this, $key));
        }
    }
}
?>
