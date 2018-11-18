<?php
namespace MagicDB;

class MagicDB implements MagicInterface {
    private $queriesExecuted = 0;

    private $dbh;
    private $isMySQLDatabase;
    private $dbPrefix;
    public $commiting = false;

    public function __construct($dsn, $username = '', $password = '', $db = '') {
        $this->dbh = new \PDO($dsn, $username, $password);
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->isMySQLDatabase = ($this->dbh->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'mysql');
        $this->dbPrefix = $db;

        $this->queriesExecuted++;
        $this->dbh->exec('CREATE TABLE IF NOT EXISTS _' . $db . ' (p VARCHAR(255), k VARCHAR(255),t TINYINT, v BLOB, PRIMARY KEY(p, k))');
    }


    function startCommit() {
        if (!$this->commiting) {
            $this->commiting = true;
            $this->dbh->beginTransaction();
            return true;
        }
        return false;
    }

    function endCommit($commitInThis) {
        if ($commitInThis) {
            $this->dbh->commit();
            $this->commiting = false;
        }
    }

    function _parentkey($parent, $key) {
        return sha1($parent . '$$' . $key);
    }

    function _execSet($parent, $key, $type, $value) {
        $this->queriesExecuted++;
        $stmt = null;
        if ($this->isMySQLDatabase)
            $stmt = $this->dbh->prepare('INSERT INTO _' . $this->dbPrefix . ' (p, k, t, v) VALUES (:parent, :key, :type, :value) ON DUPLICATE KEY UPDATE p = :parent, t = :type, v = :value;');
        else
            $stmt = $this->dbh->prepare('REPLACE INTO _' . $this->dbPrefix . ' (p, k, t, v) VALUES (:parent, :key, :type, :value)');
        $stmt->bindValue(':parent', $parent, \PDO::PARAM_STR);
        $stmt->bindValue(':key', $key, \PDO::PARAM_STR);
        $stmt->bindValue(':type', $type, \PDO::PARAM_INT);
        $stmt->bindValue(':value', $value, \PDO::PARAM_LOB);
        $stmt->execute();
    }

    function _set($parent, $key, $value, $createKey = true) {
        $commitInThis = $this->startCommit();

        if (is_array($value)) {
            $parentKey = $this->_parentkey($parent, $key);

            if ($createKey) {
                $this->_deleteAll($parentKey);
                $this->_execSet($parent, $key, 0, null);
            }

            if ($this->isMySQLDatabase)
                $stmt = $this->dbh->prepare('INSERT INTO _' . $this->dbPrefix . ' (p, k, t, v) VALUES (:parent, :key, :type, :value) ON DUPLICATE KEY UPDATE p = :parent, t = :type, v = :value;');
            else
                $stmt = $this->dbh->prepare('REPLACE INTO _' . $this->dbPrefix . ' (p, k, t, v) VALUES (:parent, :key, :type, :value)');
            $stmt->bindValue(':parent', $parentKey, \PDO::PARAM_STR);
            $arrayKeys = array();
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    $arrayKeys[] = $k;
                } else {
                    $stmt->bindValue(':key', $k, \PDO::PARAM_STR);
                    $stmt->bindValue(':type', 1, \PDO::PARAM_INT);
                    $stmt->bindValue(':value', $v, \PDO::PARAM_LOB);
                    $this->queriesExecuted++;
                    $stmt->execute();
                }
            }

            foreach ($arrayKeys as $key) {
                $this->_set($parentKey, $key, $value[$key]);
            }

            $stmt = null;
        } else
            $this->_execSet($parent, $key, 1, $value);

        $this->endCommit($commitInThis);

        return $this;
    }

    public function set($key, $value) {
        $this->_set('', $key, $value);
    }

    function _delete($parent, $key) {
        $this->queriesExecuted++;
        $stmt = $this->dbh->prepare('DELETE FROM _' . $this->dbPrefix . ' WHERE p=:parent AND k=:key');
        $stmt->bindValue(':parent', $parent, \PDO::PARAM_STR);
        $stmt->bindValue(':key', $key, \PDO::PARAM_STR);
        $stmt->execute();
    }

    function _deleteAll($parent) {
        $this->queriesExecuted++;
        $stmt = $this->dbh->prepare('SELECT *  FROM _' . $this->dbPrefix . ' WHERE p=:parent');
        $stmt->bindValue(':parent', $parent, \PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if ($result === false) {
            throw new \Exception('Fetch failed!');
        }

        if (count($result) > 0) {
            foreach ($result as $r) {
                if ($r['t'] == 0)
                    $this->_deleteAll($this->_parentkey($parent, $r['k']));
            }

            $this->queriesExecuted++;
            $stmt = $this->dbh->prepare('DELETE FROM _' . $this->dbPrefix . ' WHERE p=:parent');
            $stmt->bindValue(':parent', $parent, \PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    function _get($parent, $key) {
        $this->queriesExecuted++;
        $stmt = $this->dbh->prepare('SELECT * FROM _' . $this->dbPrefix . ' WHERE p=:parent AND k=:key');
        $stmt->bindValue(':parent', $parent, \PDO::PARAM_STR);
        $stmt->bindValue(':key', $key, \PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        } else {
            if ($result['t'] == 0) {
                return $this->_getAll($this->_parentkey($parent, $key));
            } else if ($result['t'] == 1) {
                return $result['v'];
            } else
                throw new \Exception('Unknown type.');
        }
    }

    function _getAll($parent) {
        $this->queriesExecuted++;
        $stmt = $this->dbh->prepare('SELECT * FROM _' . $this->dbPrefix . ' WHERE p=:parent');
        $stmt->bindValue(':parent', $parent, \PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if ($result === false) {
            return null;
        } else {
            $arr = array();
            foreach ($result as $r) {
                $key = $r['k'];
                if ($r['t'] == 0) {
                    $arr[$key] = $this->_getAll($this->_parentkey($parent, $key));
                } else if ($r['t'] == 1) {
                    $arr[$key] = $r['v'];
                } else
                    throw new \Exception('Unknown type.');
            }
            return $arr;
        }
    }

    function _getKeys($parent) {
        $this->queriesExecuted++;
        $stmt = $this->dbh->prepare('SELECT k FROM _' . $this->dbPrefix . ' WHERE p=:parent');
        $stmt->bindValue(':parent', $parent, \PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if ($result === false) {
            return array();
        } else {
            $arr = array();
            foreach ($result as $r) {
                $arr[] = $r['k'];
            }
            return $arr;
        }
    }

    function _type($parent, $key) {
        $this->queriesExecuted++;
        $stmt = $this->dbh->prepare('SELECT * FROM _' . $this->dbPrefix . ' WHERE p=:parent AND k=:key');
        $stmt->bindValue(':parent', $parent, \PDO::PARAM_STR);
        $stmt->bindValue(':key', $key, \PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch();

        return $result === false ? -1 : $result['t'];
    }

    function _size($parent) {
        $this->queriesExecuted++;
        $stmt = $this->dbh->prepare('SELECT count(*) as c FROM _' . $this->dbPrefix . ' WHERE p=:parent');
        $stmt->bindValue(':parent', $parent, \PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['c'];
    }

    public function get($key) {
        return new DBValue($this, null, '', $key);
    }

    public function getValue() {
        return $this->_getAll('');
    }

    public function setValue($value) {
        foreach ($value as $k => $v) {
            $this->_set('', $k, $v);
        }
    }

    public function getKeys() {
        return $this->_getKeys('');
    }

    public function getType() {
        return 0;
    }

    public function getSize() {
        return $this->_size('');
    }

    public function numQueriesExecuted() {
        return $this->queriesExecuted;
    }
}
