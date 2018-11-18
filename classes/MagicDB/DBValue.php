<?php
namespace MagicDB;

class DBValue implements MagicInterface {
    private $db;
    private $parentdb;
    private $parent;
    private $key;

    public function __construct($db, $parentdb, $parent, $key) {
        $this->db = $db;
        $this->parentdb = $parentdb;
        $this->parent = $parent;
        $this->key = $key;
    }

    public function set($key, $value) {
        $commitInThis = $this->db->startCommit();
        $this->ensurePath();
        $this->db->_set($this->db->_parentkey($this->parent, $this->key), $key, $value);
        $this->db->endCommit($commitInThis);
        return $this;
    }

    public function ensurePath() {
        $commitInThis = $this->db->startCommit();
        $this->db->_execSet($this->parent, $this->key, 0, null);

        if ($this->parentdb != null) {
            $this->parentdb->ensurePath();
        }
        $this->db->endCommit($commitInThis);
    }

    public function get($key) {
        return new DBValue($this->db, $this, $this->db->_parentkey($this->parent, $this->key), $key);
    }

    public function getValue() {
        return $this->db->_get($this->parent, $this->key);
    }

    public function setValue($value) {
        if ($this->parentdb != null) {
            $commitInThis = $this->db->startCommit();
            $this->parentdb->ensurePath();
        }
        $result = $this->db->_set($this->parent, $this->key, $value);

        if ($this->parentdb != null)
            $this->db->endCommit($commitInThis);
        return $result;
    }

    public function getKeys() {
        return $this->db->_getKeys($this->db->_parentkey($this->parent, $this->key));
    }

    public function getType() {
        return $this->db->_type($this->parent, $this->key);
    }

    public function getSize() {
        return $this->db->_size($this->db->_parentkey($this->parent, $this->key));
    }

    public function delete() {
        $commitInThis = $this->db->startCommit();
        $this->db->_delete($this->parent, $this->key);
        $this->db->_deleteAll($this->db->_parentkey($this->parent, $this->key));
        $this->db->endCommit($commitInThis);
    }

    public function numQueriesExecuted() {
        return $this->db->numQueriesExecuted();
    }
}
