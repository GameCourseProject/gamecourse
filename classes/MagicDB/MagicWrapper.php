<?php

namespace MagicDB;

class MagicWrapper extends \Wrapper {
    private $magicDB;
    private $cachedWrappers = array();
    private $gotTotalCache = false;
    private $cached;
    private $parent = null;
    private $key = null;

    public function __construct($magicDB, $cached = null, $parent = null, $key = null) {
        $this->magicDB = $magicDB;
        $this->cached = $cached;
        if ($this->cached === null)
            $this->cached = array();
        else
            $this->gotTotalCache = true;
        $this->parent = $parent;
        $this->key = $key;
    }

    function numQueriesExecuted() {
        return $this->magicDB->numQueriesExecuted();
    }

    public function &getValue() {
        if (!$this->gotTotalCache) {
           /* echo '<pre>';
            print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
            echo '</pre>';
            //print_r(\SmartBoards\Course::$coursesDb);
            echo 'before' . \SmartBoards\Course::$coursesDb->numQueriesExecuted();*/
            $this->cached = $this->magicDB->getValue();
            if ($this->parent != null)
                $this->parent->cached[$this->key] = &$this->cached;
            $this->gotTotalCache = true;
            //echo 'after' . \SmartBoards\Course::$coursesDb->numQueriesExecuted();
        }
        return $this->cached;
    }

    public function setValue($value) {
        $this->cached = $value;
        if ($this->parent != null)
            $this->parent->cached[$this->key] = &$this->cached;
        $this->gotTotalCache = true;
        $this->magicDB->setValue($value);
    }

    public function getWrapped($key, $create = true, $setParent = false) {
        if (array_key_exists($key, $this->cachedWrappers))
            return $this->cachedWrappers[$key];
        $cached = ($this->cached !== null && array_key_exists($key, $this->cached)) ? $this->cached[$key] : null;
        $wrapper = new MagicWrapper($this->magicDB->get($key), $cached, $this, $key);
        $this->cachedWrappers[$key] = $wrapper;
        return $wrapper;
    }

    public function setValueRef(&$value) {
        throw new Exception('Not implemented. Please use setValue()');
    }

    /*public function set($key, $value, $returnRaw = false) {
        $result = parent::set($key, $value, $returnRaw);
        $this->magicDB->set($key, $this->cached[$key]);
        return $result;
    }

    public function setRef($key, &$value, $returnRaw = false) {
        $result = parent::setRef($key, $value, $returnRaw);
        $this->magicDB->set($key, $this->cached[$key]);
        return $result;
    }

    public function push($value, $returnRaw = false) {
        $result = parent::push($value, $returnRaw);
        $this->magicDB->setValue($this->cached);
        return $result;
    }

    public function pushRef(&$value, $returnRaw = false) {
        $result = parent::pushRef($value, $returnRaw);
        $this->magicDB->setValue($this->cached);
        return $result;
    }

    public function delete($key, $returnRaw = false) {
        $result = parent::delete($key, $returnRaw);
        $this->magicDB->get($key)->delete();
        return $result;
    }*/

    public function getKeys() {
        return $this->magicDB->getKeys();
    }

    public function isNull() {
        return $this->magicDB->getType() === -1;
    }

    public function isArray() {
        return $this->magicDB->getType() == 0;
    }

    public function size() { // can be optimized (a lot!)
        if ($this->gotTotalCache) {
            if ($this->cached === null)
                return 0;
            return count($this->cached);
        }
        return $this->magicDB->getSize();
    }

    public function hasKey($key) {
        return $this->magicDB->get($key)->getType() != -1;
    }

    public function has($value) { // can be optimized (a lot!)
        static::unwrapValue($value);
        $data = &$this->getValue();
        if ($data != null && is_array($data))
            return in_array($value, $data);
        return false;
    }

    public function set($key, $value, $returnRaw = false) {
        static::unwrapValue($value);
        $this->magicDB->set($key, $value);
        if ($this->cached === null)
            $this->cached = array();
        $this->cached[$key] = $value;
        if ($returnRaw) {
            $data = &$this->getValue(); // get total object (can be very expensive)
            return $data;
        }
        return $this;
    }

    public function setRef($key, &$value, $returnRaw = false) {
        static::unwrapValue($value);
        $this->magicDB->set($key, $value);
        if ($this->cached === null)
            $this->cached = array();
        $this->cached[$key] = &$value;
        if ($returnRaw) {
            $data = &$this->getValue(); // get total object (can be very expensive)
            return $data;
        }
        return $this;
    }

    public function push($value, $returnRaw = false) {
        static::unwrapValue($value);
        $data = &$this->getValue();
        if ($data == null || !is_array($data)) {
            $data = array();
            $data[] = $value;
        } else
            $data[] = $value;
        end($data);
        $this->magicDB->set(key($data), $value);
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
        } else
            $data[] = &$value;
        end($data);
        $this->magicDB->set(key($data), $value);
        if ($returnRaw)
            return $data;
        return $this;
    }

    public function &get($key, $default = null) {
        static::unwrapValue($default);
        $magicWrapped = $this->magicDB->get($key);
        $value = $magicWrapped->getValue();
        if ($value == null)
            return $default;
        return $value;
    }

    public function delete($key, $returnRaw = false) {
        $this->magicDB->get($key)->delete();
        if ($this->cached !== null && array_key_exists($key, $this->cached))
            unset($this->cached[$key]);
        if ($returnRaw) {
            $data = &$this->getValue();
            return $data;
        }
        return $this;
    }

    /*public function filter($callback) {
        $keys = $this->getKeys();
        return new \ValueWrapper(array_filter($keys, function($key) use ($callback) {
            return $callback($key, $this->getWrapped($key));
        }, ARRAY_FILTER_USE_BOTH));
    }*/

    public function getIterator() {
        if ($this->gotTotalCache) {
            if ($this->cached === null)
                $keys = array();
            else
                $keys = array_keys($this->cached);
        } else
            $keys = $this->magicDB->getKeys();
        foreach($keys as $key) {
            if (array_key_exists($key, $this->cachedWrappers)) {
                yield $key => $this->cachedWrappers[$key];
            } else {
                $cached = ($this->cached !== null && array_key_exists($key, $this->cached)) ? $this->cached[$key] : null;
                $wrapper = new MagicWrapper($this->magicDB->get($key), $cached, $this, $key);
                $this->cachedWrappers[$key] = $wrapper;
                yield $key => $wrapper;
            }
        }
    }
}
