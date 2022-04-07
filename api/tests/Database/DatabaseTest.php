<?php
namespace Database;

use GameCourse\Core\Core;
use GameCourse\User\Auth;
use GameCourse\User\User;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function setUpBeforeClass(): void
    {
        Core::database()->deleteAll(User::TABLE_USER);
        Core::database()->resetAutoIncrement(User::TABLE_USER);
        Core::database()->resetAutoIncrement(Auth::TABLE_AUTH);
    }

    protected function tearDown(): void
    {
        Core::database()->deleteAll(User::TABLE_USER);
        Core::database()->resetAutoIncrement(User::TABLE_USER);
        Core::database()->resetAutoIncrement(Auth::TABLE_AUTH);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function executeQueryShowTables()
    {
        $query = "SHOW TABLES LIKE '" . User::TABLE_USER . "';";
        $tables = Core::database()->executeQuery($query)->fetchAll(PDO::FETCH_ASSOC);
        $this->assertIsArray($tables);
        $this->assertCount(1, $tables);
    }

    /**
     * @test
     */
    public function executeQueryInsert()
    {
        $query = "INSERT INTO " . User::TABLE_USER . " VALUES (1, 'John Doe', 'johndoe@email.com', 'MEIC-A', '', 123456, false, true);";
        Core::database()->executeQuery($query);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
    }

    /**
     * @test
     */
    public function executeQuerySelect()
    {
        $query = "INSERT INTO " . User::TABLE_USER . " VALUES (1, 'John Doe', 'johndoe@email.com', 'MEIC-A', '', 123456, false, true);";
        Core::database()->executeQuery($query);

        $query = "SELECT * FROM " . User::TABLE_USER;
        $users = Core::database()->executeQuery($query)->fetchAll(PDO::FETCH_ASSOC);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
    }

    /**
     * @test
     */
    public function executeQueryDelete()
    {
        $query = "INSERT INTO " . User::TABLE_USER . " VALUES (1, 'John Doe', 'johndoe@email.com', 'MEIC-A', '', 123456, false, true);";
        Core::database()->executeQuery($query);

        $query = "DELETE FROM " . User::TABLE_USER . " WHERE 1";
        Core::database()->executeQuery($query);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(0, $users);
    }

    /**
     * @test
     */
    public function executeQueryFailure()
    {
        $query = "QUERY WITH ERROR";
        $this->expectException(PDOException::class);
        Core::database()->executeQuery($query);
    }

    /**
     * @test
     */
    public function selectFirst()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $first = Core::database()->select(User::TABLE_USER);
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertEquals("John Doe", $first["name"]);
    }

    /**
     * @test
     */
    public function selectFirstJoinedTables()
    {
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["game_course_user_id" => $id]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["game_course_user_id" => $id]);

        $first = Core::database()->select(User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.game_course_user_id=u.id");
        $this->assertIsArray($first);
        $this->assertCount(11, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertArrayHasKey("username", $first);
        $this->assertEquals("John Doe", $first["name"]);
    }

    /**
     * @test
     */
    public function selectFirstWhereCondition()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $first = Core::database()->select(User::TABLE_USER, ["name" => "John Doe"]);
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertEquals("John Doe", $first["name"]);
    }

    /**
     * @test
     */
    public function selectFirstWhereMultipleConditions()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $first = Core::database()->select(User::TABLE_USER, ["id" => 1, "name" => "John Doe"]);
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertEquals("John Doe", $first["name"]);
    }

    /**
     * @test
     */
    public function selectFirstWhereConditionWithWildcard()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $first = Core::database()->select(User::TABLE_USER, [], "*", null, [], [], ["name" => "John%"]);
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertEquals("John Doe", $first["name"]);
    }

    /**
     * @test
     */
    public function selectFirstFilterColumn()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $first = Core::database()->select(User::TABLE_USER, ["name" => "John Doe"], "name");
        $this->assertIsString($first);
        $this->assertEquals("John Doe", $first);
    }

    /**
     * @test
     */
    public function selectFirstFilterMultipleColumns()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $first = Core::database()->select(User::TABLE_USER, ["name" => "John Doe"], "id, name");
        $this->assertIsArray($first);
        $this->assertCount(2, $first);
        $this->assertArrayHasKey("id", $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertEquals("1", $first["id"]);
        $this->assertEquals("John Doe", $first["name"]);
    }

    /**
     * @test
     */
    public function selectFirstJoinedTablesFilterMultipleColumns()
    {
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["game_course_user_id" => $id]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["game_course_user_id" => $id]);

        $first = Core::database()->select(User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a", ["u.name" => "John Doe"], "name, a.username");
        $this->assertIsArray($first);
        $this->assertCount(2, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertArrayHasKey("username", $first);
        $this->assertEquals("John Doe", $first["name"]);
        $this->assertNull($first["username"]);
    }

    /**
     * @test
     */
    public function selectFirstWhenOrdering()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $first = Core::database()->select(User::TABLE_USER, [], "*", "name");
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertEquals("Anna Doe", $first["name"]);
    }

    /**
     * @test
     */
    public function selectFirstWhenOrderingMultiple()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        $first = Core::database()->select(User::TABLE_USER, [], "*", "name, email");
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertArrayHasKey("email", $first);
        $this->assertEquals("Anna Doe", $first["name"]);
        $this->assertEquals("a", $first["email"]);
    }

    /**
     * @test
     */
    public function selectFirstWhenOrderingMultipleDifferentOrders()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        $first = Core::database()->select(User::TABLE_USER, [], "*", "name ASC, email DESC");
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertArrayHasKey("email", $first);
        $this->assertEquals("Anna Doe", $first["name"]);
        $this->assertEquals("b", $first["email"]);
    }

    /**
     * @test
     */
    public function selectFirstWhereNot()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        $first = Core::database()->select(User::TABLE_USER, [], "*", null, [["name", "Anna Doe"]]);
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertEquals("John Doe", $first["name"]);
    }

    /**
     * @test
     */
    public function selectFirstWhereNotMultiple()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        $first = Core::database()->select(User::TABLE_USER, [], "*", null, [["name", "John Doe"], ["email", "b"]]);
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertArrayHasKey("email", $first);
        $this->assertEquals("Anna Doe", $first["name"]);
        $this->assertEquals("a", $first["email"]);
    }

    /**
     * @test
     */
    public function selectFirstWhereCompare()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        $first = Core::database()->select(User::TABLE_USER, [], "*", null, [], [["id", ">", 1]]);
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertArrayHasKey("email", $first);
        $this->assertEquals("Anna Doe", $first["name"]);
        $this->assertEquals("a", $first["email"]);
    }

    /**
     * @test
     */
    public function selectFirstWhereCompareMultiple()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        $first = Core::database()->select(User::TABLE_USER, [], "*", null, [], [["id", ">", 1], ["email", "!=", "a"]]);
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertArrayHasKey("email", $first);
        $this->assertEquals("Anna Doe", $first["name"]);
        $this->assertEquals("b", $first["email"]);
    }

    // TODO: select multiple

    /**
     * @test
     */
    public function insertDefaultValues()
    {
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertEquals(["id" => $id, "name" => "John Doe", "email" => null, "major" => null, "nickname" => null,
            "studentNumber" => null, "isAdmin" => "0", "isActive" => "1"], $users[0]);
    }

    /**
     * @test
     */
    public function insertSomeValues()
    {
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "johndoe@email.com"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertEquals(["id" => $id, "name" => "John Doe", "email" => "johndoe@email.com", "major" => null,
            "nickname" => null, "studentNumber" => null, "isAdmin" => "0", "isActive" => "1"], $users[0]);
    }

    /**
     * @test
     */
    public function insertFailure()
    {
        $this->expectException(PDOException::class);
        Core::database()->insert("table_doesnt_exist", ["name" => "John Doe"]);
    }

    // TODO: update

    // TODO: delete

    // TODO: utils
}
