<?php
namespace Utils;

use Error;
use PHPUnit\Framework\TestCase;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
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

    public function uploadFileProvider(): array
    {
        return [
            "image" => [
                "data:image/jpeg;base64,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",
                "PNGImage.png"
            ]
        ];
    }

    public function compareVersionsBiggerThanProvider(): array
    {
        return [
            "zero" => ["2.2.0", "0.0.0"],
            "close" => ["2.2.10", "2.2.9"],
            "two digits" => ["10.0.0", "2.2.0"],
            "middle" => ["2.2.4", "2.1.4"]
        ];
    }

    public function compareVersionsSmallerThanProvider(): array
    {
        return [
            "zero" => ["0.0.0", "2.2.0"],
            "close" => ["2.2.9", "2.2.10"],
            "two digits" => ["2.2.0", "10.0.0"],
            "middle" => ["2.1.4", "2.2.4"]
        ];
    }

    public function compareVersionsEqualToProvider(): array
    {
        return [
            "zero" => ["0.0.0", "0.0.0"],
            "default" => ["2.2.0", "2.2.0"]
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function getDirectoryContents()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");
        mkdir(ROOT_PATH . "tests/Utils/dir1/.dir13", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/.file5.txt", "");

        // When
        $contents = Utils::getDirectoryContents(ROOT_PATH . "tests/Utils/dir1");

        // Then
        $this->assertIsArray($contents);
        $this->assertCount(3, $contents);

        $dir11 = $contents[0];
        $this->assertIsArray($dir11);
        $this->assertCount(3, array_keys($dir11));
        $this->assertArrayHasKey("name", $dir11);
        $this->assertArrayHasKey("type", $dir11);
        $this->assertArrayHasKey("contents", $dir11);
        $this->assertEquals("dir11", $dir11["name"]);
        $this->assertEquals("folder", $dir11["type"]);
        $this->assertCount(2, $dir11["contents"]);

        $file1 = $dir11["contents"][0];
        $this->assertIsArray($file1);
        $this->assertCount(3, array_keys($file1));
        $this->assertArrayHasKey("name", $file1);
        $this->assertArrayHasKey("type", $file1);
        $this->assertArrayHasKey("extension", $file1);
        $this->assertEquals("file1.txt", $file1["name"]);
        $this->assertEquals("file", $file1["type"]);
        $this->assertEquals(".txt", $file1["extension"]);

        $file2 = $dir11["contents"][1];
        $this->assertIsArray($file2);
        $this->assertCount(3, array_keys($file2));
        $this->assertArrayHasKey("name", $file2);
        $this->assertArrayHasKey("type", $file2);
        $this->assertArrayHasKey("extension", $file2);
        $this->assertEquals("file2.txt", $file2["name"]);
        $this->assertEquals("file", $file2["type"]);
        $this->assertEquals(".txt", $file2["extension"]);

        $dir12 = $contents[1];
        $this->assertIsArray($dir12);
        $this->assertCount(3, array_keys($dir12));
        $this->assertArrayHasKey("name", $dir12);
        $this->assertArrayHasKey("type", $dir12);
        $this->assertArrayHasKey("contents", $dir12);
        $this->assertEquals("dir12", $dir12["name"]);
        $this->assertEquals("folder", $dir12["type"]);
        $this->assertCount(1, $dir12["contents"]);

        $file3 = $dir12["contents"][0];
        $this->assertIsArray($file3);
        $this->assertCount(3, array_keys($file3));
        $this->assertArrayHasKey("name", $file3);
        $this->assertArrayHasKey("type", $file3);
        $this->assertArrayHasKey("extension", $file3);
        $this->assertEquals("file3.txt", $file3["name"]);
        $this->assertEquals("file", $file3["type"]);
        $this->assertEquals(".txt", $file3["extension"]);

        $file4 = $contents[2];
        $this->assertIsArray($file4);
        $this->assertCount(3, array_keys($file4));
        $this->assertArrayHasKey("name", $file4);
        $this->assertArrayHasKey("type", $file4);
        $this->assertArrayHasKey("extension", $file4);
        $this->assertEquals("file4.txt", $file4["name"]);
        $this->assertEquals("file", $file4["type"]);
        $this->assertEquals(".txt", $file4["extension"]);

        // Clean up
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");
    }


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
    public function copyDirectoryWithExceptions()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        Utils::copyDirectory(ROOT_PATH . "tests/Utils/dir1/", ROOT_PATH . "tests/Utils/dir2/", ["file4.txt", "dir11/file2.txt", "dir12"]);

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
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir2/dir12"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11/file1.txt"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11/file2.txt"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir2/dir12/file3.txt"));
        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir2/file4.txt"));

        // Clean up
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir2");
    }

    /**
     * @test
     */
    public function copyDirectoryDeleteOriginal()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        Utils::copyDirectory(ROOT_PATH . "tests/Utils/dir1/", ROOT_PATH . "tests/Utils/dir2/", [], true);

        // Then
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir12"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11/file1.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11/file2.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir12/file3.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/file4.txt"));

        $this->assertFalse(file_exists(ROOT_PATH . "tests/Utils/dir1"));

        // Clean up
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir2");
    }

    /**
     * @test
     */
    public function copyDirectoryToExistingDirectory()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");
        mkdir(ROOT_PATH . "tests/Utils/dir2/dir11", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir2/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir2/dir11/file6.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir2/file5.txt", "");

        // When
        Utils::copyDirectory(ROOT_PATH . "tests/Utils/dir1/", ROOT_PATH . "tests/Utils/dir2/", [], true);

        // Then
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir12"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11/file1.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11/file2.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir11/file6.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/dir12/file3.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/file4.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir2/file5.txt"));

        // Clean up
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
     * @dataProvider uploadFileProvider
     */
    public function uploadFile(string $base64, string $filename)
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        $path1 = Utils::uploadFile(ROOT_PATH . "tests/Utils/dir1/dir12", $base64, $filename);
        $path2 = Utils::uploadFile(ROOT_PATH . "tests/Utils/dir1/", $base64, $filename);

        // Then
        $this->assertTrue(file_exists($path1));
        $this->assertTrue(file_exists($path2));

        // Clean up
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");
    }

    /**
     * @test
     * @dataProvider uploadFileProvider
     */
    public function uploadFileDirectoryDoesntExist(string $base64, string $filename)
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        $path = Utils::uploadFile(ROOT_PATH . "tests/Utils/dir1/dir12", $base64, $filename);

        // Then
        $this->assertTrue(file_exists($path));

        // Clean up
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");
    }

    /**
     * @test
     */
    public function uploadFileIsNotDirectory()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        $this->expectException(Error::class);
        try {
            Utils::uploadFile(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "", "");
        } catch (Error $error) {
            // Clean up
            Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");

            throw new Error($error->getMessage());
        }
    }


    /**
     * @test
     */
    public function deleteFile()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        Utils::deleteFile(ROOT_PATH . "tests/Utils/dir1/dir11/", "file1.txt");
        Utils::deleteFile(ROOT_PATH . "tests/Utils/dir1/dir11/", "file2.txt");
        Utils::deleteFile(ROOT_PATH . "tests/Utils/dir1", "file4.txt", false);

        // Then
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1"));

        // Clean up
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");
    }

    /**
     * @test
     */
    public function deleteFileFileDoesntExist()
    {
        // Given
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir11", 0777, true);
        mkdir(ROOT_PATH . "tests/Utils/dir1/dir12", 0777, true);
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt", "");
        file_put_contents(ROOT_PATH . "tests/Utils/dir1/file4.txt", "");

        // When
        Utils::deleteFile(ROOT_PATH . "tests/Utils/dir1/dir11/", "file5.txt");

        // Then
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file1.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir11/file2.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/dir12/file3.txt"));
        $this->assertTrue(file_exists(ROOT_PATH . "tests/Utils/dir1/file4.txt"));

        // Clean up
        Utils::deleteDirectory(ROOT_PATH . "tests/Utils/dir1");
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
    public function strStartsWith()
    {
        // True
        $this->assertTrue(Utils::strStartsWith("abc", ""));
        $this->assertTrue(Utils::strStartsWith("abc", "a"));
        $this->assertTrue(Utils::strStartsWith("abc", "ab"));
        $this->assertTrue(Utils::strStartsWith("abc", "abc"));
        $this->assertTrue(Utils::strStartsWith("aabc", "aa"));

        // False
        $this->assertFalse(Utils::strStartsWith("abc", "b"));
        $this->assertFalse(Utils::strStartsWith("abc", "d"));
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
    public function swapNonENChars()
    {
        $portuguese = "Produção de Conteúdos Multimédia";
        $this->assertEquals("Producao de Conteudos Multimedia", Utils::swapNonENChars($portuguese));

        $norwegian = "Bjørn Håvard";
        $this->assertEquals("Bjorn Havard", Utils::swapNonENChars($norwegian));

        $capital = "Água";
        $this->assertEquals("Agua", Utils::swapNonENChars($capital));
    }


    /**
     * @test
     */
    public function importFromCSVWithHeader()
    {
        // Given
        $headers = ["Header #1", "Header #2", "Header #3"];
        $file = implode(", ", $headers) . "\n";
        $file .= "Item 11, Item12, Item13\n";
        $file .= "Item 21, Item22, Item23\n";
        $file .= "Item 31, Item32, Item33";

        // Then
        $nrItemsImported = Utils::importFromCSV($headers, function ($item, $indexes) use ($headers) {
            $this->assertIsArray($indexes);
            $this->assertSameSize($headers, $indexes);
            $this->assertEquals(0, $indexes[$headers[0]]);
            $this->assertEquals(1, $indexes[$headers[1]]);
            $this->assertEquals(2, $indexes[$headers[2]]);
            return 1;
        }, $file);
        $this->assertEquals(3, $nrItemsImported);
    }

    /**
     * @test
     */
    public function importFromCSVWithoutHeader()
    {
        // Given
        $headers = ["Header #1", "Header #2", "Header #3"];
        $file  = "Item 11, Item12, Item13\n";
        $file .= "Item 21, Item22, Item23\n";
        $file .= "Item 31, Item32, Item33";

        // Then
        $nrItemsImported = Utils::importFromCSV($headers, function ($item, $indexes) use ($headers) {
            $this->assertIsArray($indexes);
            $this->assertSameSize($headers, $indexes);
            $this->assertEquals(0, $indexes[$headers[0]]);
            $this->assertEquals(1, $indexes[$headers[1]]);
            $this->assertEquals(2, $indexes[$headers[2]]);
            return 1;
        }, $file);
        $this->assertEquals(3, $nrItemsImported);
    }

    /**
     * @test
     */
    public function importFromCSVEmpty()
    {
        // Given
        $headers = ["Header #1", "Header #2", "Header #3"];
        $file = "";

        // Then
        $nrItemsImported = Utils::importFromCSV($headers, function ($item, $indexes) use ($headers) {
            $this->assertIsArray($indexes);
            $this->assertSameSize($headers, $indexes);
            $this->assertEquals(0, $indexes[$headers[0]]);
            $this->assertEquals(1, $indexes[$headers[1]]);
            $this->assertEquals(2, $indexes[$headers[2]]);
            return 1;
        }, $file);
        $this->assertEquals(0, $nrItemsImported);
    }


    /**
     * @test
     */
    public function exportToCSVWithHeader()
    {
        // Given
        $headers = ["Header #1", "Header #2", "Header #3"];

        // When
        $file = Utils::exportToCSV([
            ["Header #1" => "Item 11", "Header #2" => "Item 12", "Header #3" => "Item 13"],
            ["Header #1" => "Item 21", "Header #2" => "Item 22", "Header #3" => "Item 23"],
            ["Header #1" => "Item 31", "Header #2" => "Item 32", "Header #3" => "Item 33"],
            ],
            function ($item) { return [$item["Header #1"], $item["Header #2"], $item["Header #3"]]; },
            $headers);

        // Then
        $this->assertEquals("Header #1,Header #2,Header #3\nItem 11,Item 12,Item 13\nItem 21,Item 22,Item 23\nItem 31,Item 32,Item 33", $file);
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


    /**
     * @test
     * @dataProvider compareVersionsBiggerThanProvider
     */
    public function compareVersionsBiggerThan(string $v1, string $v2)
    {
        $this->assertEquals(1, Utils::compareVersions($v1, $v2));
    }

    /**
     * @test
     * @dataProvider compareVersionsSmallerThanProvider
     */
    public function compareVersionsSmallerThan(string $v1, string $v2)
    {
        $this->assertEquals(-1, Utils::compareVersions($v1, $v2));
    }

    /**
     * @test
     * @dataProvider compareVersionsEqualToProvider
     */
    public function compareVersionsEqualTo(string $v1, string $v2)
    {
        $this->assertEquals(0, Utils::compareVersions($v1, $v2));
    }

    /**
     * @test
     */
    public function compareVersionsDifferentNrOfParts()
    {
        $this->assertEquals(-1, Utils::compareVersions("2.1.0", "2.2"));
        $this->assertEquals(1, Utils::compareVersions("2.2", "2.1.0"));
        $this->assertEquals(0, Utils::compareVersions("2.2", "2.2.0"));
    }
}
