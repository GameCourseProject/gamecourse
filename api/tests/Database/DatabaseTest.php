<?php
namespace Database;

use GameCourse\Core\Auth;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\User\User;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class DatabaseTest extends TestCase
{
    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass();
    }

    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([User::TABLE_USER]);
    }

    protected function onNotSuccessfulTest(Throwable $t): void
    {
        $this->tearDown();
        parent::onNotSuccessfulTest($t);
    }

    public static function tearDownAfterClass(): void
    {
        TestingUtils::tearDownAfterClass();
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // Executing query

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


    // Selecting 1st

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
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "johndoe", "auth_service" => AuthService::FENIX]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "annadoe", "auth_service" => AuthService::FENIX]);

        $first = Core::database()->select(User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.user=u.id", [], "*", "id");
        $this->assertIsArray($first);
        $this->assertCount(12, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertArrayHasKey("username", $first);
        $this->assertEquals("John Doe", $first["name"]);
        $this->assertEquals("johndoe", $first["username"]);
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
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "johndoe"]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "annadoe"]);

        $first = Core::database()->select(User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.user=u.id", ["u.name" => "John Doe"], "name, a.username");
        $this->assertIsArray($first);
        $this->assertCount(2, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertArrayHasKey("username", $first);
        $this->assertEquals("John Doe", $first["name"]);
        $this->assertEquals("johndoe", $first["username"]);
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

    /**
     * @test
     */
    public function selectFirstWithLikeParams()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $first = Core::database()->select(User::TABLE_USER, [], "*", null, [], [], ["name" => "John%"]);
        $this->assertIsArray($first);
        $this->assertCount(8, $first);
        $this->assertArrayHasKey("name", $first);
        $this->assertEquals("John Doe", $first["name"]);
    }


    // Selecting multiple

    /**
     * @test
     */
    public function selectMultiple()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        $this->assertCount(8, $users[0]);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
        $this->assertCount(8, $users[1]);
        $this->assertArrayHasKey("name", $users[1]);
        $this->assertEquals("Anna Doe", $users[1]["name"]);
    }

    /**
     * @test
     */
    public function selectMultipleJoinedTables()
    {
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "johndoe", "auth_service" => AuthService::FENIX]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "annadoe", "auth_service" => AuthService::FENIX]);

        $users = Core::database()->selectMultiple(User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.user=u.id", [], "*", "id");
        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        $this->assertCount(12, $users[0]);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertArrayHasKey("username", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
        $this->assertEquals("johndoe", $users[0]["username"]);
        $this->assertCount(12, $users[1]);
        $this->assertArrayHasKey("name", $users[1]);
        $this->assertArrayHasKey("username", $users[1]);
        $this->assertEquals("Anna Doe", $users[1]["name"]);
        $this->assertEquals("annadoe", $users[1]["username"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhereCondition()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, ["name" => "John Doe"]);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhereMultipleConditions()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, ["id" => 1, "name" => "John Doe"]);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function selectMultipleFilterColumn()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, ["name" => "John Doe"], "name");
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertCount(1, $users[0]);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function selectMultipleFilterMultipleColumns()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, ["name" => "John Doe"], "id, name");
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertCount(2, $users[0]);
        $this->assertArrayHasKey("id", $users[0]);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("1", $users[0]["id"]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function selectMultipleJoinedTablesFilterMultipleColumns()
    {
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "johndoe"]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "annadoe"]);

        $users = Core::database()->selectMultiple(User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.user=u.id", ["u.name" => "John Doe"], "name, a.username");
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertCount(2, $users[0]);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertArrayHasKey("username", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
        $this->assertEquals("johndoe", $users[0]["username"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhenOrdering()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, [], "*", "name");
        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("Anna Doe", $users[0]["name"]);
        $this->assertArrayHasKey("name", $users[1]);
        $this->assertEquals("John Doe", $users[1]["name"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhenOrderingMultiple()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, [], "*", "name, email");
        $this->assertIsArray($users);
        $this->assertCount(3, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertArrayHasKey("email", $users[0]);
        $this->assertEquals("Anna Doe", $users[0]["name"]);
        $this->assertEquals("a", $users[0]["email"]);
        $this->assertArrayHasKey("name", $users[1]);
        $this->assertArrayHasKey("email", $users[1]);
        $this->assertEquals("Anna Doe", $users[1]["name"]);
        $this->assertEquals("b", $users[1]["email"]);
        $this->assertArrayHasKey("name", $users[2]);
        $this->assertArrayHasKey("email", $users[2]);
        $this->assertEquals("John Doe", $users[2]["name"]);
        $this->assertEquals("c", $users[2]["email"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhenOrderingMultipleDifferentOrders()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, [], "*", "name ASC, email DESC");
        $this->assertIsArray($users);
        $this->assertCount(3, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertArrayHasKey("email", $users[0]);
        $this->assertEquals("Anna Doe", $users[0]["name"]);
        $this->assertEquals("b", $users[0]["email"]);
        $this->assertArrayHasKey("name", $users[1]);
        $this->assertArrayHasKey("email", $users[1]);
        $this->assertEquals("Anna Doe", $users[1]["name"]);
        $this->assertEquals("a", $users[1]["email"]);
        $this->assertArrayHasKey("name", $users[2]);
        $this->assertArrayHasKey("email", $users[2]);
        $this->assertEquals("John Doe", $users[2]["name"]);
        $this->assertEquals("c", $users[2]["email"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhereNot()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, [], "*", null, [["name", "Anna Doe"]]);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhereNotMultiple()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, [], "*", null, [["name", "John Doe"], ["email", "b"]]);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertArrayHasKey("email", $users[0]);
        $this->assertEquals("Anna Doe", $users[0]["name"]);
        $this->assertEquals("a", $users[0]["email"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhereCompare()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, [], "*", null, [], [["id", ">", 1]]);
        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertArrayHasKey("email", $users[0]);
        $this->assertEquals("Anna Doe", $users[0]["name"]);
        $this->assertEquals("a", $users[0]["email"]);
        $this->assertArrayHasKey("name", $users[1]);
        $this->assertArrayHasKey("email", $users[1]);
        $this->assertEquals("Anna Doe", $users[1]["name"]);
        $this->assertEquals("b", $users[1]["email"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhereCompareMultiple()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, [], "*", null, [], [["id", ">", 1], ["email", "!=", "a"]]);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertArrayHasKey("email", $users[0]);
        $this->assertEquals("Anna Doe", $users[0]["name"]);
        $this->assertEquals("b", $users[0]["email"]);
    }

    /**
     * @test
     */
    public function selectMultipleWhenGrouping()
    {
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "johndoe", "auth_service" => AuthService::FENIX]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "annadoe", "auth_service" => AuthService::GOOGLE]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Julia Doe"]);
        Core::database()->insert(Auth::TABLE_AUTH, ["user" => $id, "username" => "juliadoe", "auth_service" => AuthService::GOOGLE]);

        $authServices = Core::database()->selectMultiple(Auth::TABLE_AUTH, [], "count(user), auth_service", null, [], [], "auth_service");
        $this->assertIsArray($authServices);
        $this->assertCount(2, $authServices);

        $this->assertArrayHasKey("auth_service", $authServices[0]);
        $this->assertArrayHasKey("count(user)", $authServices[0]);
        $this->assertEquals(AuthService::FENIX, $authServices[0]["auth_service"]);
        $this->assertEquals(1, intval($authServices[0]["count(user)"]));

        $this->assertArrayHasKey("auth_service", $authServices[1]);
        $this->assertArrayHasKey("count(user)", $authServices[1]);
        $this->assertEquals(AuthService::GOOGLE, $authServices[1]["auth_service"]);
        $this->assertEquals(2, intval($authServices[1]["count(user)"]));
    }

    /**
     * @test
     */
    public function selectMultipleWithLikeParams()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER, [], "*", null, [], [], null, ["name" => "Anna%"]);
        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("Anna Doe", $users[0]["name"]);
        $this->assertArrayHasKey("name", $users[1]);
        $this->assertEquals("Anna Doe", $users[1]["name"]);
    }


    // Inserting

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


    // Updating

    /**
     * @test
     */
    public function updateAll()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->update(User::TABLE_USER, ["name" => "Julia Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        foreach ($users as $user) {
            $this->assertArrayHasKey("name", $user);
            $this->assertEquals("Julia Doe", $user["name"]);
        }
    }

    /**
     * @test
     */
    public function updateWhereCondition()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->update(User::TABLE_USER, ["name" => "Julia Doe"], ["name" => "Anna Doe"]);
        $name = Core::database()->select(User::TABLE_USER, ["id" => $id], "name");
        $this->assertIsString($name);
        $this->assertEquals("Julia Doe", $name);
    }

    /**
     * @test
     */
    public function updateWhereMultipleConditions()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->update(User::TABLE_USER, ["name" => "Julia Doe"], ["id" => $id, "name" => "Anna Doe"]);
        $name = Core::database()->select(User::TABLE_USER, ["id" => $id], "name");
        $this->assertIsString($name);
        $this->assertEquals("Julia Doe", $name);
    }

    /**
     * @test
     */
    public function updateWhereNotCondition()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->update(User::TABLE_USER, ["name" => "Julia Doe"], [], [["name", "John Doe"]]);
        $name = Core::database()->select(User::TABLE_USER, ["id" => $id], "name");
        $this->assertIsString($name);
        $this->assertEquals("Julia Doe", $name);
    }

    /**
     * @test
     */
    public function updateWhereNotMultipleConditions()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->update(User::TABLE_USER, ["name" => "Julia Doe"], [], [["id", 1], ["name", "John Doe"]]);
        $name = Core::database()->select(User::TABLE_USER, ["id" => $id], "name");
        $this->assertIsString($name);
        $this->assertEquals("Julia Doe", $name);
    }

    /**
     * @test
     */
    public function updateWhereCompareCondition()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->update(User::TABLE_USER, ["name" => "Julia Doe"], [], [], [["name", "=", "Anna Doe"]]);
        $name = Core::database()->select(User::TABLE_USER, ["id" => $id], "name");
        $this->assertIsString($name);
        $this->assertEquals("Julia Doe", $name);
    }

    /**
     * @test
     */
    public function updateWhereCompareMultipleConditions()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        $id = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->update(User::TABLE_USER, ["name" => "Julia Doe"], [], [], [["id", ">", 1], ["name", "=", "Anna Doe"]]);
        $name = Core::database()->select(User::TABLE_USER, ["id" => $id], "name");
        $this->assertIsString($name);
        $this->assertEquals("Julia Doe", $name);
    }

    /**
     * @test
     */
    public function updateWithLikeParams()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        $id1 = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        $id2 = Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        Core::database()->update(User::TABLE_USER, ["name" => "Julia Doe"], [], [], [], ["name" => "Anna%"]);
        $name1 = Core::database()->select(User::TABLE_USER, ["id" => $id1], "name");
        $this->assertIsString($name1);
        $this->assertEquals("Julia Doe", $name1);
        $name2 = Core::database()->select(User::TABLE_USER, ["id" => $id2], "name");
        $this->assertIsString($name2);
        $this->assertEquals("Julia Doe", $name2);
    }


    // Deleting

    /**
     * @test
     */
    public function deleteAllEntries()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->delete(User::TABLE_USER);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(0, $users);
    }

    /**
     * @test
     */
    public function deleteWhereCondition()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->delete(User::TABLE_USER, ["name" => "Anna Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function deleteWhereMultipleConditions()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->delete(User::TABLE_USER, ["id" => 2, "name" => "Anna Doe"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function deleteWhereNotCondition()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->delete(User::TABLE_USER, [], [["name", "John Doe"]]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function deleteWhereNotMultipleConditions()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->delete(User::TABLE_USER, [], [["id", 1], ["name", "John Doe"]]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function deleteWhereCompareCondition()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->delete(User::TABLE_USER, [], [], [["name", "=", "Anna Doe"]]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function deleteWhereCompareMultipleConditions()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->delete(User::TABLE_USER, [], [], [["id", ">", 1], ["name", "=", "Anna Doe"]]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function deleteWithLikeParams()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe", "email" => "c"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "a"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe", "email" => "b"]);
        Core::database()->delete(User::TABLE_USER, [], [], [], ["name" => "Anna%"]);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertCount(1, $users);
        $this->assertArrayHasKey("name", $users[0]);
        $this->assertEquals("John Doe", $users[0]["name"]);
    }

    /**
     * @test
     */
    public function deleteAll()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        Core::database()->deleteAll(User::TABLE_USER);
        $users = Core::database()->selectMultiple(User::TABLE_USER);
        $this->assertIsArray($users);
        $this->assertCount(0, $users);
    }


    // Getting last ID

    /**
     * @test
     */
    public function getLastId()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $this->assertEquals(2, Core::database()->getLastId());
    }

    /**
     * @test
     */
    public function getLastIdNoInsertMade()
    {
        $this->assertEquals(0, Core::database()->getLastId());
    }


    // Checking whether table exists

    /**
     * @test
     */
    public function tableExists()
    {
        $this->assertTrue(Core::database()->tableExists(User::TABLE_USER));
    }

    /**
     * @test
     */
    public function tableDoesntExist()
    {
        $this->assertFalse(Core::database()->tableExists("table_doesnt_exist"));
    }


    // Checking whether column exists in table

    /**
     * @test
     */
    public function columnExists()
    {
        $this->assertTrue(Core::database()->columnExists(User::TABLE_USER, "name"));
    }

    /**
     * @test
     */
    public function columnDoesntExist()
    {
        $this->assertFalse(Core::database()->columnExists(User::TABLE_USER, "column_doesnt_exist"));
    }


    // Setting foreign key checks

    /**
     * @test
     */
    public function setForeignKeyChecks()
    {
        Core::database()->setForeignKeyChecks(false);
        Core::database()->insert(Auth::TABLE_AUTH, [
            "user" => 1,
            "username" => "ist12345",
            "auth_service" => AuthService::FENIX
        ]);
        $this->assertNotEmpty(Core::database()->selectMultiple(Auth::TABLE_AUTH));

        Core::database()->setForeignKeyChecks(true);
        $this->expectException(PDOException::class);
        Core::database()->insert(Auth::TABLE_AUTH, [
            "user" => 2,
            "username" => "ist54321",
            "auth_service" => AuthService::FENIX
        ]);
    }


    // Resetting auto increment value

    /**
     * @test
     */
    public function resetAutoIncrement()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $lastID = Core::database()->getLastId();
        $this->assertEquals(2, $lastID);
        Core::database()->deleteAll(User::TABLE_USER);
        Core::database()->resetAutoIncrement(User::TABLE_USER);
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        $lastID = Core::database()->getLastId();
        $this->assertEquals(1, $lastID);
    }

    /**
     * @test
     */
    public function resetAutoIncrementToValue()
    {
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        Core::database()->insert(User::TABLE_USER, ["name" => "Anna Doe"]);
        $lastID = Core::database()->getLastId();
        $this->assertEquals(2, $lastID);
        Core::database()->deleteAll(User::TABLE_USER);
        Core::database()->resetAutoIncrement(User::TABLE_USER, 5);
        Core::database()->insert(User::TABLE_USER, ["name" => "John Doe"]);
        $lastID = Core::database()->getLastId();
        $this->assertEquals(5, $lastID);
    }
}
