<?php
namespace Database;

use PDO;
use PDOException;
use PDOStatement;

/**
 * The database access layer which will be used to interact with
 * the underlying MySQL database.
 */
class Database
{
    private static $instance; // singleton
    private $db;

    private function __construct()
    {
        try {
            $this->db = new PDO(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            echo ("Could not connect to database '" . CONNECTION_STRING . "'.\n");
        }
    }

    public static function get(): Database
    {
        if (self::$instance == null) self::$instance = new Database();
        return self::$instance;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Query Execution ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Executes any given SQL query.
     *
     * @example executeQuery("SHOW TABLES;")
     * @example executeQuery("SELECT * FROM auth WHERE game_course_user_id=123;")
     *
     * @param string $sql
     * @return false|PDOStatement
     */
    public function executeQuery(string $sql)
    {
        return $this->db->query($sql);
    }

    /**
     * Executes a given SQL query with parameters.
     * @param string $sql
     * @param array|null $data
     * @return false|PDOStatement
     */
    private function executeQueryWithParams(string $sql, ?array $data)
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $stmt;
    }

    /**
     * Takes an array and creates a string with $key=$value(,&)....
     * then adds it to the SQL query string.
     *
     * @param string $sql
     * @param array $data
     * @param string $separator
     * @param array $whereNot
     * @param array $whereCompare
     * @param array $likeParams
     */
    private function dataToQuery(string &$sql, array &$data, string $separator, array $whereNot = [], array $whereCompare = [], array $likeParams = [])
    {
        // Processing where conditions
        foreach ($data as $key => $value) { // [key => value]
            if (is_null($value) && ($separator == "&&" || $separator == "||"))
                $sql .= $key . " is ? " . $separator;
            else
                $sql .= $key . '= ? ' . $separator;
        }
        $data = array_values($data);

        // Processing where not conditions
        foreach ($whereNot as $not) { // [[key , value], ...]
            if (is_null($not[1]) && ($separator == "&&" || $separator == "||"))
                $sql .= $not[0] . " is not ? " . $separator;
            else
                $sql .= $not[0] . "!= ? " . $separator;
            $data[] = $not[1];
        }

        // Processing comparison conditions
        foreach ($whereCompare as $keyCompVal) { // [["key","<",5], ...]
            $sql .= $keyCompVal[0] . $keyCompVal[1] . " ? " . $separator;
            $data[] = $keyCompVal[2];
        }

        // Processing like parameters
        foreach ($likeParams as $key => $value) {
            $sql .= $key . " LIKE ? " . $separator;
            $data[] = $value;
        }

        $sql = substr($sql, 0, - (strlen($separator)));
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Selecting --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Selects the first entry from database table(s).
     * Options for filtering, selecting certain columns and ordering.
     *
     * @example select 1st entry from 'auth' table --> select("auth")
     * @example select 1st entry from 'auth' table where conditions apply --> select("auth", ["id" => 123, "username" => "ist123456"])
     * @example select 1st entry from 'auth' table and only show columns 'id' and 'username' --> select("auth", [], "id, username")
     * @example select 1st entry from 'auth' table when ordered by 'id' column --> select("auth", [], "*", "id")
     * @example select 1st entry from 'auth' table when ordered by 'id' (asc) and 'name' (desc) column --> select("auth", [], "*", "id ASC, name DESC")
     * @example select 1st entry from 'auth' table where conditions don't apply --> select("auth", [], "*", null, [["authentication_service", "fenix"]])
     * @example select 1st entry from 'auth' table where comparisons apply --> select("auth", [], "*", null, [], [["id", "<", 5]])
     *
     * @param string $table
     * @param array|null $where
     * @param string $field
     * @param string|null $orderBy
     * @param array|null $whereNot
     * @param array|null $whereCompare
     * @param array|null $likeParams
     * @return mixed|void
     */
    public function select(string $table, array $where = [], string $field = '*', string $orderBy = null, array $whereNot = [], array $whereCompare = [], array $likeParams = [])
    {
        $sql = "SELECT " . $field . " FROM " . $table;

        // Process conditions
        if (!empty($where) || !empty($whereNot) || !empty($whereCompare) || !empty($likeParams)) {
            $sql .= " WHERE ";
            $this->dataToQuery($sql, $where, '&&', $whereNot, $whereCompare, $likeParams);
        }

        // Process order by
        if (!empty($orderBy)) $sql .= " ORDER BY " . $orderBy;

        $sql .= ';';

        // Execute Query
        var_dump($sql);
        $result = $this->executeQueryWithParams($sql, $where)->fetch(PDO::FETCH_ASSOC);

        if ($field == '*' or strpos($field, ','))
            return $result;

        if (($pos = strpos($field, ".")) !== false)
            return $result[substr($field, $pos + 1)];

        if (is_array($result)) {
            if (array_key_exists($field, $result))
                return $result[$field];
        } else {
            return $result;
        }
    }

    /**
     * Selects entries from database table(s).
     * Options for filtering, selecting certain columns, ordering and grouping.
     *
     * @example select all entry from 'auth' table --> select("auth")
     * @example select all entries from 'auth' table where conditions apply --> select("auth", ["id" => 123, "username" => "ist123456"])
     * @example select all entries from 'auth' table and only show columns 'id' and 'username' --> select("auth", [], "id, username")
     * @example select all entries from 'auth' table and order by 'id' column --> select("auth", [], "*", "id")
     * @example select all entries from 'auth' table where conditions don't apply --> select("auth", [], "*", null, [["authentication_service", "fenix"]])
     * @example select all entries from 'auth' table where comparisons apply --> select("auth", [], "*", null, [], [["id", "<", 5]])
     *
     * @param $table
     * @param $where
     * @param $field
     * @param $orderBy
     * @param $whereNot
     * @param $whereCompare
     * @param $group
     * @param $likeParams
     * @return array|false
     */
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


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Inserting -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Inserts data into a database table.
     * If no data is given, it will insert the default values for each column.
     * Returns last inserted ID.
     *
     * @example insert default values in 'auth' table --> insert("auth")
     * @example insert some values in 'auth' table --> insert("auth", ["game_course_user_id" => 123, "username" => "ist123456", "authentication_service" => "fenix"])
     *
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert(string $table, array $data = []): int
    {
        $sql = "INSERT INTO " . $table;
        if (empty($data)) {
            $sql .= " VALUES (default)";
        } else {
            $sql .= " SET ";
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

    /**
     * Resets auto increment to 1.
     *
     * @param string $table
     * @return void
     */
    public function resetAutoIncrement(string $table)
    {
        $this->executeQuery("ALTER TABLE " . $table . " AUTO_INCREMENT = 1");
    }
}
