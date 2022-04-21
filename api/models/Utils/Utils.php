<?php
namespace Utils;

use DateTime;
use Error;

/**
 * Holds a set of utility functions that can be used
 * throughout the whole application.
 */
class Utils
{

    /*** ---------------------------------------------------- ***/
    /*** ------------------ File Structure ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets contents of a directory.
     * Ignores items that start with '.' or '..'.
     *
     * @param string $dir
     * @return array
     */
    public static function getDirectoryContents(string $dir): array
    {
        $contents = [];
        $objects = array_diff(scandir($dir), ["..", "."]);

        foreach ($objects as $object) {
            if ($object[0] == "." || $object[0] == "..") continue;

            $objectPath = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($objectPath) && !is_link($objectPath)) { // directory
                $contents[] = [
                  "name" => $object,
                  "type" => "folder",
                  "contents" => self::getDirectoryContents($objectPath)
                ];

            } else { // file
                $temp = explode(".", $object);
                $extension = "." . end($temp);
                $contents[] = [
                    "name" => $object,
                    "type" => "file",
                    "extension" => $extension
                ];
            }
        }
        return $contents;
    }

    /**
     * Deletes a given directory and its contents.
     * Options to only delete contents and for exceptions that
     * should not be deleted.
     *
     * @example deleteDirectory("course_data") --> deletes contents and directory 'course_data'
     * @example deleteDirectory("course_data", false) --> deletes only contents of directory 'course_data'
     * @example deleteDirectory("course_data", false, ["defaultData", "keep.txt"]) --> deletes only contents of directory
     *          'course_data', but keeps directory 'defaultData' and file 'keep.txt' and their parent directories
     *
     * @param string $dir
     * @param bool $deleteSelf
     * @param array $exceptions
     * @return void
     */
    public static function deleteDirectory(string $dir, bool $deleteSelf = true, array $exceptions = [])
    {
        // Transform from relative to absolute paths
        foreach ($exceptions as &$exception) {
            $exception = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $dir . DIRECTORY_SEPARATOR . $exception);
        }
        $dir = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $dir);
        self::deleteDirectoryHelper($dir, $deleteSelf, $exceptions);
    }

    private static function deleteDirectoryHelper(string $dir, bool $deleteSelf = true, array $exceptions = [])
    {
        if (!is_dir($dir))
            throw new Error("'" . $dir . "' is not a directory.");

        foreach ($exceptions as $exception) {
            if (str_contains($exception, $dir))
                $deleteSelf = false;
        }

        if (!in_array($dir, $exceptions)) { // not in exceptions
            $objects = array_diff(scandir($dir), ["..", "."]);
            foreach ($objects as $object) {
                $objectPath = $dir . DIRECTORY_SEPARATOR . $object;
                if (is_dir($objectPath) && !is_link($objectPath)) // directory
                    self::deleteDirectoryHelper($objectPath, true, $exceptions);
                else if (!in_array($objectPath, $exceptions)) // file
                    unlink($objectPath);
            }
            if ($deleteSelf) rmdir($dir);
        }
    }

    /**
     * Copies directory's contents to a new location, keeping the
     * same file structure as the original directory.
     * Option for exceptions that should not be copied.
     *
     * @example copyDirectory("<path>/dir1/", "<path>/dir2/") --> copies contents of dir1 to dir2
     *
     * @param string $dir
     * @param string $copyTo
     * @param array $exceptions
     * @return void
     */
    public static function copyDirectory(string $dir, string $copyTo, array $exceptions = [])
    {
        if (in_array(PHP_OS, ["WIN32", "WINNT", "Windows"])) {
            // Change directory separator
            $dir = str_replace("/", "\\", $dir);
            $copyTo = str_replace("/", "\\", $copyTo);
            shell_exec("xcopy " . $dir . " " . $copyTo . " /E");

        } elseif (in_array(PHP_OS, ["Linux", "Unix"]))
            shell_exec("cp -R " . $dir . " " . $copyTo);
        else throw new Error("Can't copy directory: OS is neither Windows nor Linux based.");

        if (count($exceptions) != 0) {
            foreach ($exceptions as $exception) {
                $exception = $copyTo . "/" . $exception;
                if (is_dir($exception)) self::deleteDirectory($exception);
                else unlink($exception);
            }
        }
    }

    /**
     * Uploads a given file to a given directory. It will create directory
     * if it doesn't yet exist.
     *
     * @param string $to
     * @param string $base64
     * @param string $filename
     * @return string
     */
    public static function uploadFile(string $to, string $base64, string $filename): string
    {
        if (!file_exists($to)) mkdir($to, 077, true);
        if (!is_dir($to))
            throw new Error("Can't upload file to '" . $to . "' since it isn't a directory.");

        preg_match('/\w/', substr($to, -1), $matches);
        if (count($matches) != 0) $to .= "/";
        $path = $to . $filename;

        $file = base64_decode(preg_replace('#^data:\w+/\w+;base64,#i', '', $base64));
        file_put_contents($path, $file);
        return $path;
    }

    /**
     * Deletes a given file from a given directory.
     * Option to delete given directory if it becomes empty.
     *
     * @param string $from
     * @param string $filename
     * @param bool $deleteIfEmpty
     * @return void
     */
    public static function deleteFile(string $from, string $filename, bool $deleteIfEmpty = true)
    {
        if (!is_dir($from))
            throw new Error("Can't delete file from '" . $from . "' since it isn't a directory.");

        preg_match('/\w/', substr($from, -1), $matches);
        if (count($matches) != 0) $from .= "/";
        $path = $from . $filename;

        if (file_exists($path))
            unlink($path);

        // Delete directory if empty
        if (count(glob($from . "/*")) === 0 && $deleteIfEmpty) Utils::deleteDirectory($from);
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Validations ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Checks if e-mail is in a valid format.
     *
     * @param string|null $email
     * @return bool
     */
    public static function validateEmail(?string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        $prefix = explode("@", $email)[0];
        $domain = explode("@", $email)[1];
        if (Utils::strEndsWith($prefix, "-") || str_contains($prefix, "#")) return false;
        foreach (explode(".", $domain) as $part) if (strlen($part) < 2) return false;
        return true;
    }

    /**
     * Checks if date is in a given format.
     *
     * @param string|null $date
     * @param string $format
     * @return bool
     */
    public static function validateDate(?string $date, string $format): bool
    {
        return !!DateTime::createFromFormat($format, $date);
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- String Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Checks if a string ends with a given substring.
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function strEndsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if (!$length) return true;
        return substr($haystack, -$length) === $needle;
    }

    /**
     * Replaces non-English characters by their English counterparts.
     *
     * @param string $str
     * @return string
     */
    public static function swapNonENChars(string $str): string
    {
        $str = preg_replace("/[ãáâàåä]/u", "a", $str);
        $str = preg_replace("/[ÃÁÂÀÅÄ]/u", "A", $str);

        $str = preg_replace("/[óôõòøö]/u", "o", $str);
        $str = preg_replace("/[ÓÔÕÒØÖ]/u", "O", $str);

        $str = preg_replace("/ç/u", "c", $str);
        $str = preg_replace("/Ç/u", "C", $str);

        $str = preg_replace("/[éêè]/u", "e", $str);
        $str = preg_replace("/[ÉÊÈ]/u", "E", $str);

        $str = preg_replace("/[íì]/u", "i", $str);
        $str = preg_replace("/[ÍÌ]/u", "I", $str);

        $str = preg_replace("/[úùüû]/u", "u", $str);
        $str = preg_replace("/[ÚÙÜÛ]/u", "U", $str);

        $str = preg_replace("/ñ/u", "n", $str);
        $str = preg_replace("/Ñ/u", "N", $str);

        $str = preg_replace("/ß/u", "b", $str);

        $str = preg_replace("/æ/u", "ae", $str);
        $str = preg_replace("/Æ/u", "AE", $str);

        return preg_replace("/[^a-zA-Z\d_ ]/", "", $str);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- CSV ------------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Detects the used separator for a .csv file.
     *
     * @param string $csvFile
     * @return string|null
     */
    public static function detectSeparator(string $csvFile): ?string
    {
        if (empty($csvFile)) return null;
        $separators = [";" => 0, "," => 0, "\t" => 0, "|" => 0];
        $firstLine = array_filter(explode("\n", $csvFile), function ($line) { return !empty($line); })[0];
        foreach ($separators as $separator => &$count) {
            $count = count(str_getcsv($firstLine, $separator));
        }
        return array_search(max($separators), $separators);
    }
}
