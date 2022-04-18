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
     * Gets all files in a directory, even if they're organized
     * in subdirectories.
     * Ignores files that start with '.' or '..'.
     *
     * @param string $baseDir
     * @param ...$files
     * @return array
     */
    public static function discoverFiles(string $baseDir, ...$files): array
    {
        $discoveredFiles = [];
        foreach ($files as $filePart) {
            if (strpos($filePart, '//') === 0 || strpos($filePart, 'http') === 0) {
                $discoveredFiles[] = $filePart;
                continue;
            }
            else if (strpos($filePart, '/') === 0) $file = $filePart;
            else $file = $baseDir . (strrpos($baseDir, '/') == strlen($baseDir) - 1 ? '' : '/') . $filePart;

            if (is_dir($file)) self::discoverSubFiles($file, $discoveredFiles);
            else $discoveredFiles[] = $file;
        }
        return $discoveredFiles;
    } // FIXME: check if function is needed; if so create tests

    private static function discoverSubFiles($dirPath, &$discoveredFiles) {
        $dh = dir($dirPath);
        while (($fileName = $dh->read()) !== false) {
            $file = $dirPath . $fileName;
            if ($fileName == '.' || $fileName == '..')
                continue;
            if (is_dir($file))
                static::discoverSubFiles($file . '/', $discoveredFiles);
            else if (!in_array($file, $discoveredFiles)) {
                $discoveredFiles[] = $file;
            }
        }
    } // FIXME: check if function is needed

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
     *
     * @example copyDirectory("<path>/dir1/", "<path>/dir2/") --> copies contents of dir1 to dir2
     *
     * @param string $dir
     * @param string $copyTo
     * @return void
     */
    public static function copyDirectory(string $dir, string $copyTo)
    {
        if (in_array(PHP_OS, ["WIN32", "WINNT", "Windows"])) {
            // Change directory separator
            $dir = str_replace("/", "\\", $dir);
            $copyTo = str_replace("/", "\\", $copyTo);
            shell_exec("xcopy " . $dir . " " . $copyTo . " /E");

        } elseif (in_array(PHP_OS, ["Linux", "Unix"]))
            shell_exec("cp -R " . $dir . " " . $copyTo);
        else throw new Error("Can't copy directory: OS is neither Windows nor Linux based.");
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
