<?php

namespace MagicDB;

/**
 * This class is responsible for the interaction with the database.
 */
class SQLDB
{
    private $db;

    public function __construct($dsn, $username = '', $password = '')
    {
        try {
            $this->db = new \PDO($dsn, $username, $password);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            //echo $e->getMessage();
            echo ("Could not connect to database '" . $dsn . "'.\n");
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Query Execution ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public function executeQuery($sql)
    {
        try {
            $result = $this->db->query($sql);
        } catch (\PDOException $e) {
            //echo $sql . "<br>" . $e->getMessage() . "<br>";
            throw new \PDOException($e);
        }
        return $result;
    }

    public function executeQueryWithParams($sql, $data)
    {
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($data);
        } catch (\PDOException $e) {
            //echo "<br>" . $sql . "<br>" . $e->getMessage() . "<br>";
            //print_r(array_values($data));
            throw new \PDOException($e);
        }
        return $stmt;
    }

    /**
     * Takes an array and creates a string with $key=$value(,&)....
     * then adds to sql query str
     *
     * @param $sql
     * @param $data
     * @param $separator
     * @param array $whereNot
     * @param array $whereCompare
     * @param false $add
     */
    private function dataToQuery(&$sql, &$data, $separator, array $whereNot = [], array $whereCompare = [], bool $add = false)
    {
        foreach ($data as $key => $value) {
            if ($add)
                $sql .= $key . '= ' . $key . ' + ' . $value . $separator;
            elseif ($value === null && ($separator == "&&" || $separator == "||"))
                $sql .= $key . " is ? " . $separator;
            else
                $sql .= $key . '= ? ' . $separator;
            //$sql.=$key.'= :'.$key.' '.$separator;
        }
        $data = array_values($data);
        foreach ($whereNot as $not) { // [key , value] 
            if ($not[1] === null && ($separator == "&&" || $separator == "||"))
                $sql .= $not[0] . " is not ? " . $separator;
            else
                $sql .= $not[0] . "!= ? " . $separator;
            array_push($data, $not[1]);
        }

        foreach ($whereCompare as $keyCompVal) {
            //ex: ["key","<",5]]
            $sql .= $keyCompVal[0] . $keyCompVal[1] . " ? " . $separator;
            array_push($data, $keyCompVal[2]);
        }
        $sql = substr($sql, 0, - (strlen($separator)));
    }



    /*** ---------------------------------------------------- ***/
    /*** --------------------- Inserting -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Inserts data into database table.
     * @example "insert into user set name="Example",id=80000,username=ist1800000";
     *
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert(string $table, array $data = []): int
    {
        $sql = "insert into " . $table;
        if ($data == []) {
            $sql .= " values(default)";
        } else {
            $sql .= " set ";
            $this->dataToQuery($sql, $data, ',');
        }
        $sql .= ";";
        $this->executeQueryWithParams($sql, $data);
        return $this->getLastId();
    }



    /*** ---------------------------------------------------- ***/
    /*** --------------------- Updating --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Updates data from database table.
     * @example "update user set name="Example", email="a@a.a" where id=80000;"
     *
     * @param string $table
     * @param array $data
     * @param array|null $where
     * @param array $whereNot
     * @param array $whereCompare
     */
    public function update(string $table, array $data, array $where = null, array $whereNot = [], array $whereCompare = [])
    {
        $sql = "update " . $table . " set ";
        $this->dataToQuery($sql, $data, ',');
        if ($where) {
            $sql .= " where ";
            $this->dataToQuery($sql, $where, '&&', $whereNot, $whereCompare);
            $data = array_merge($data, $where);
        }
        $sql .= ';';
        $this->executeQueryWithParams($sql, $data);
    }

    public function updateAdd($table, $collumQuantity, $where, $whereNot = [], $whereCompare = [])
    {
        //example: update user set n=n+1 where id=80000;
        $sql = "update " . $table . " set ";
        $this->dataToQuery($sql, $collumQuantity, ',', [], true);
        $sql .= " where ";
        $this->dataToQuery($sql, $where, '&&', $whereNot, $whereCompare);
        $sql .= ';';
        $this->executeQueryWithParams($sql, $where);
    }



    /*** ---------------------------------------------------- ***/
    /*** --------------------- Deleting --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Deletes data from database table.
     *
     * @param string $table
     * @param array $where
     * @param null $likeParams
     * @param array $whereNot
     * @param array $whereCompare
     */
    public function delete(string $table, array $where, $likeParams = null, array $whereNot = [], array $whereCompare = [])
    {
        $sql = "delete from " . $table . " where ";
        $this->dataToQuery($sql, $where, '&&', $whereNot, $whereCompare);
        if ($likeParams != null) {
            foreach ($likeParams as $key => $value) {
                $sql .= " && " . $key . " like ? ";
            }
            $where = array_merge($where, array_values($likeParams));
        }
        $sql .= ';';
        $this->executeQueryWithParams($sql, $where);
    }

    /**
     * Deletes all data from database table.
     *
     * @param string $table
     */
    public function deleteAll(string $table)
    {
        $sql = "delete from " . $table . ";";
        $this->executeQuery($sql);
    }



    /*** ---------------------------------------------------- ***/
    /*** -------------------- Selecting --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Selects data from database table.
     * @example select id from user where username='ist181205';
     *
     * @param string $table
     * @param array|null $where
     * @param string $field
     * @param string|null $orderBy
     * @param array $whereNot
     * @param array $whereCompare
     * @return mixed|void
     */
    public function select(string $table, array $where = null, string $field = '*', string $orderBy = null, array $whereNot = [], array $whereCompare = [])
    {
        //ToDo: devia juntar as 2 funçoes select, devia aceitar array de fields,
        $sql = "select " . $field . " from " . $table;
        if ($where) {
            $sql .= " where ";
            $this->dataToQuery($sql, $where, '&&', $whereNot, $whereCompare);
        }
        if ($orderBy) {
            $sql .= " order by " . $orderBy;
        }

        $sql .= ';';
        $result = $this->executeQueryWithParams($sql, $where);
        $returnVal = $result->fetch(\PDO::FETCH_ASSOC);
        if ($field == '*' or strpos($field, ',')) {
            return $returnVal;
        }
        if ($pos = strpos($field, ".") !== false)
            return $returnVal[substr($field, $pos + 1)];

        if (is_array($returnVal)) {
            if (array_key_exists($field, $returnVal)) {
                return $returnVal[$field];
            }
        } else {
            return $returnVal;
        }
    }

    public function selectMultiple($table, $where = null, $field = '*', $orderBy = null, $whereNot = [], $whereCompare = [], $group = null, $likeParams = null)
    {
        //example: select * from course where isActive=true;
        $sql = "select " . $field . " from " . $table;
        if ($where) {
            $sql .= " where ";
            $this->dataToQuery($sql, $where, '&&', $whereNot, $whereCompare);
        }
        if ($likeParams != null) {
            foreach ($likeParams as $key => $value) {
                $sql .= " && " . $key . " like ? ";
            }
            $where = array_merge($where, array_values($likeParams));
        }
        if ($group) {
            $sql .= " group by " . $group;
        }
        if ($orderBy) {
            $sql .= " order by " . $orderBy;
        }

        $sql .= ';';
        $result = $this->executeQueryWithParams($sql, $where);
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function selectHierarchy($table, $tableUnion = null, $where = null, $field = '*')
    {
        $sql = "WITH RECURSIVE aspects AS (SELECT " . $field . " FROM " . $table;
        if ($where) {
            $sql .= " where ";
            $this->dataToQuery($sql, $where, '||');
        }

        // if ($group) {
        //     $sql .= " group by " . $group;
        // }

        $sql .= " UNION SELECT " . $field . " FROM " . $tableUnion . " JOIN aspects ON aspects.id=vp.parentId)";
        $sql .= " \nSELECT * from aspects";
        //array_push($where, $where[0]);

        $sql .= ';';
        $result = $this->executeQueryWithParams($sql, $where);
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function selectMultipleSegmented($table, $where, $field = "*", $orderBy = null, $group = null)
    {
        $sql = "select " . $field . " from " . $table;
        if ($where) {
            $sql .= " where " . $where;
        }
        if ($group) {
            $sql .= " group by " . $group;
        }
        if ($orderBy) {
            $sql .= " order by " . $orderBy;
        }

        $sql .= ';';
        $result = $this->executeQuery($sql);
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }



    /*** ---------------------------------------------------- ***/
    /*** --------------------- Utilities -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets the last auto_increment id after an insertion in the database
     *
     * @return int
     */
    public function getLastId(): int
    {
        $result = $this->executeQuery("SELECT LAST_INSERT_ID();");
        return intval($result->fetch()[0]);
    }

    /**
     * Checks if given column exists in database table.
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function columnExists(string $table, string $column): bool
    {
        $result = $this->executeQuery("show columns from " . $table . " like '" . $column . "';");
        return $result->fetch()[0] == $column;
    }

    /**
     * Checks if given table exists in database.
     *
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        return !empty($this->executeQuery("show tables like '" . $table . "';")->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Sets foreign key checks.
     *
     * @param bool $status
     */
    public function setForeignKeyChecks(bool $status)
    {
        $this->executeQuery("SET FOREIGN_KEY_CHECKS=" . ($status ? 1 : 0) .";");
    }
}
