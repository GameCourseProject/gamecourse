<?php
namespace Utils;

use GameCourse\Course\Course;

/**
 * Holds a set of utility functions that can be used
 * throughout the whole application.
 */
class Utils
{

    /*** ---------------------------------------------------- ***/
    /*** ------------------ File Structure ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public static function discoverFiles($baseDir, ...$files): array
    {
        $discoveredFiles = array();
        foreach($files as $filePart) {
            if (strpos($filePart, '//') === 0 || strpos($filePart, 'http') === 0) {
                $discoveredFiles[] = $filePart;
                continue;
            }
            else if (strpos($filePart, '/') === 0) $file = $filePart;
            else $file = $baseDir . (strrpos($baseDir, '/') == strlen($baseDir) - 1 ? '' : '/') . $filePart;

            if (is_dir($file)) static::discoverSubFiles($file, $discoveredFiles);
            else $discoveredFiles[] = $file;
        }
        return $discoveredFiles;
    }

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
    }

    public static function deleteDirectory($dir, $exceptions = array(), $deleteSelf = true)   // Deletes directory and all contents
    {
        if (is_dir($dir) && !in_array($dir, $exceptions)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object))
                        self::deleteDirectory($dir . DIRECTORY_SEPARATOR . $object, $exceptions);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            if ($deleteSelf) rmdir($dir);
        }
    }

    private static function unlinkDir($dirname) {
        $dh = dir($dirname);
        while (($fileName = $dh->read()) !== false) {
            $file = $dirname . $fileName;
            if ($fileName == '.' || $fileName == '..')
                continue;
            if (is_dir($file))
                static::unlinkDir($file . '/');
            else
                unlink($file);
        }
        $dh->close();
        rmdir($dirname);
    }

    public static function unlink($filename) {
        if (!file_exists($filename))
            return;
        if (is_dir($filename))
            static::unlinkDir($filename . '/');
        else
            unlink($filename);
    }

    public static function copyFolder($folder,$newLocation){
        $dir = opendir($folder);
        @mkdir($newLocation);
        $file = readdir($dir);
        while($file !== false ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($folder . '/' . $file) ) {
                    Utils::copyFolder($folder . '/' . $file,$newLocation . '/' . $file);
                }
                else {
                    copy($folder . '/' . $file,$newLocation . '/' . $file);
                }
            }
            $file = readdir($dir);
        }
        closedir($dir);
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Validations ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function validateEmail($email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        return true;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- String Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Transforms a given URL path:
     *  - from absolute to relative
     *  - from relative to absolute
     *
     * @example absolute -> relative
     *  URL: http://localhost/gamecourse/api/course_data/<courseFolder>/skills/<skillName>/<filename>
     *  NEW URL: skills/<skillName>/<filename>
     *
     * @example relative -> absolute
     *  URL: skills/<skillName>/<filename>
     *  NEW URL: http://localhost/gamecourse/api/course_data/<courseFolder>/skills/<skillName>/<filename>
     *
     * @param string $url
     * @param string $to (absolute | relative)
     * @param int $courseId
     * @return string
     */
    public static function transformURL(string $url, string $to, int $courseId): string
    {
        $courseDataFolder = Course::getCourseDataFolder($courseId);
        $courseDataFolderPath = API_URL . "/" . $courseDataFolder . "/";

        if ($to === "absolute" && strpos($url, 'http') !== 0) return $courseDataFolderPath . $url;
        elseif ($to === "relative" && strpos($url, API_URL) === 0) return str_replace($courseDataFolderPath, "", $url);
        return $url;
    }

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
