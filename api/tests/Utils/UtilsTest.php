<?php
namespace Utils;

use PHPUnit\Framework\TestCase;

/**
 * NOTE: only run tests outside the production environment
 *       as it will change the database directly
 */
class UtilsTest extends TestCase
{
    /*** ---------------------------------------------------- ***/
    /*** ------------------ Data Providers ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public function validEmailProvider(): array
    {
        return [
            "valid prefix 1" => ["abc-d@mail.com"],
            "valid prefix 2" => ["abc.def@mail.com"],
            "valid prefix 3" => ["abc@mail.com"],
            "valid prefix 4" => ["abc_def@mail.com"],
            "valid domain 1" => ["abc.def@mail.cc"],
            "valid domain 2" => ["abc.def@mail-archive.com"],
            "valid domain 3" => ["abc.def@mail.org"],
            "valid domain 4" => ["abc.def@mail.com"]
        ];
    }

    public function invalidEmailProvider(): array
    {
        return [
            "null" => [null],
            "empty" => [""],
            "not a string" => [123],
            "invalid prefix 1" => ["abc-@mail.com"],
            "invalid prefix 2" => ["abc..def@mail.com"],
            "invalid prefix 3" => [".abc@mail.com"],
            "invalid prefix 4" => ["abc#def@mail.com"],
            "invalid domain 1" => ["abc.def@mail.c"],
            "invalid domain 2" => ["abc.def@mail#archive.com"],
            "invalid domain 3" => ["abc.def@mail"],
            "invalid domain 4" => ["abc.def@mail..com"]
        ];
    }

    public function validDateProvider(): array
    {
        return [
            "yyyy-mm-dd HH:mm:ss" => [date("Y-m-d H:i:s", time()), "Y-m-d H:i:s"],
            "yyyy-mm-dd" => [date("Y-m-d", time()), "Y-m-d"],
            "HH:mm:ss" => [date("H:i:s", time()), "H:i:s"],
        ];
    }

    public function invalidDateProvider(): array
    {
        return [
            "null" => [null, "Y-m-d H:i:s"],
            "yyyy-mm-dd HH:mm:ss" => [date("Y-m-d", time()), "Y-m-d H:i:s"],
            "yyyy-mm-dd" => [date("Y-m-d H:i:s", time()), "Y-m-d"],
            "HH:mm:ss" => [date("Y-m-d", time()), "H:i:s"],
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function deleteDirectory()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");

        // Then
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/file4.txt"));
    }

    /**
     * @test
     */
    public function deleteDirectoryOnlyContents()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1", false);

        // Then
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/file4.txt"));

        // After
        rmdir(ROOT_PATH . "tests/Utils/dir1");
    }

    /**
     * @test
     */
    public function deleteDirectoryWithExceptions()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1", false, ["file4.txt", "dir11/file2.txt", "dir12"]);

        // Then
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/file4.txt"));

        // After
        unlink(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt");
        rmdir(ROOT_PATH . "tests/Utils/dir1/dir11");
        unlink(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt");
        rmdir(ROOT_PATH . "tests/Utils/dir1/dir12");
        unlink(ROOT_PATH . "tests/Utils/dir1/file4.txt");
        rmdir(ROOT_PATH . "tests/Utils/dir1");
    }

    /**
     * @test
     */
    public function deleteDirectoryWithExceptionsTryToDeleteSelf()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1", true, ["file4.txt", "dir11/file2.txt", "dir12"]);

        // Then
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/file4.txt"));

        // After
        unlink(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt");
        rmdir(ROOT_PATH . "tests/Utils/dir1/dir11");
        unlink(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt");
        rmdir(ROOT_PATH . "tests/Utils/dir1/dir12");
        unlink(ROOT_PATH . "tests/Utils/dir1/file4.txt");
        rmdir(ROOT_PATH . "tests/Utils/dir1");
    }

    /**
     * @test
     */
    public function deleteDirectoryEmpty()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1", 0777, true);

        // When
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");

        // Then
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1"));
    }


    /**
     * @test
     */
    public function copyDirectory()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        Utils::copyDirectory(ROOT_PATH . "tests/Utils/dir1/", ROOT_PATH . "tests/Utils/dir2/");

        // Then
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/file4.txt"));

        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir12"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11/file1.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11/file2.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir12/file3.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/file4.txt"));

        // Clean up
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir2");
    }

    /**
     * @test
     */
    public function copyDirectoryEmpty()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1", 0777, true);

        // When
        Utils::copyDirectory(ROOT_PATH . "tests/Utils/dir1/", ROOT_PATH . "tests/Utils/dir2/");

        // Then
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1"));
        $this->assertCount(0, glob(ROOT_PATH . "tests/Utils/dir1/*"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2"));
        $this->assertCount(0, glob(ROOT_PATH . "tests/Utils/dir2/*"));

        // Clean up
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir2");
    }


    /**
     * @test
     * @dataProvider validEmailProvider
     */
    public function validateEmailValidEmail(string $email)
    {
        $this->assertTrue(Utils::validateEmail($email));
    }

    /**
     * @test
     * @dataProvider invalidEmailProvider
     */
    public function validateEmailInvalidEmail($email)
    {
        $this->assertFalse(Utils::validateEmail($email));
    }

    /**
     * @test
     * @dataProvider validDateProvider
     */
    public function validateDateValidDate(string $date, string $format)
    {
        $this->assertTrue(Utils::validateDate($date, $format));
    }

    /**
     * @test
     * @dataProvider invalidDateProvider
     */
    public function validateDateInvalidDate(?string $date, string $format)
    {
        $this->assertFalse(Utils::validateDate($date, $format));
    }


    /**
     * @test
     */
    public function strEndsWith()
    {
        // True
        $this->assertTrue(Utils::strEndsWith("abc", ""));
        $this->assertTrue(Utils::strEndsWith("abc", "c"));
        $this->assertTrue(Utils::strEndsWith("abc", "bc"));
        $this->assertTrue(Utils::strEndsWith("abc", "abc"));
        $this->assertTrue(Utils::strEndsWith("aabc", "abc"));

        // False
        $this->assertFalse(Utils::strEndsWith("abc", "b"));
        $this->assertFalse(Utils::strEndsWith("abc", "d"));
    }


    /**
     * @test
     */
    public function detectSeparatorComma()
    {
        $file = "name,email,major,nickname,studentNumber,username,authentication_service,isAdmin,isActive\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1\n";
        $this->assertEquals(",", Utils::detectSeparator($file));
    }

    /**
     * @test
     */
    public function detectSeparatorSemiColon()
    {
        $file = "name;email;major;nickname;studentNumber;username;authentication_service;isAdmin;isActive\n";
        $file .= "Sabri M'Barki;sabri.m.barki@efrei.net;MEIC-T;Sabri M'Barki;100956;ist1100956;fenix;1;1\n";
        $file .= "Inês Albano;ines.albano@tecnico.ulisboa.pt;MEIC-A;;87664;ist187664;linkedin;0;1\n";
        $this->assertEquals(";", Utils::detectSeparator($file));
    }

    /**
     * @test
     */
    public function detectSeparatorVerticalBar()
    {
        $file = "name|email|major|nickname|studentNumber|username|authentication_service|isAdmin|isActive\n";
        $file .= "Sabri M'Barki|sabri.m.barki@efrei.net|MEIC-T|Sabri M'Barki|100956|ist1100956|fenix|1|1\n";
        $file .= "Inês Albano|ines.albano@tecnico.ulisboa.pt|MEIC-A||87664|ist187664|linkedin|0|1\n";
        $this->assertEquals("|", Utils::detectSeparator($file));
    }

    /**
     * @test
     */
    public function detectSeparatorEmptyFile()
    {
        $file = "";
        $this->assertNull(Utils::detectSeparator($file));
    }
}
