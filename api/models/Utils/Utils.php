<?php
namespace Utils;

use DateTime;
use Exception;
use GameCourse\Core\Core;

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
     * Gets the number of items in a directory.
     *
     * @param string $dir
     * @return int
     * @throws Exception
     */
    public static function getDirectorySize(string $dir): int
    {
        if (!file_exists($dir)) throw new Exception("'" . $dir . "' doesn't exist.");
        if (!is_dir($dir)) throw new Exception("'" . $dir . "' is not a directory.");
        return count(glob($dir . "/*"));
    }

    /**
     * Gets directory name from its path.
     *
     * @param string $path
     * @return string
     */
    public static function getDirectoryName(string $path): string
    {
        $parts = explode("/", $path);
        return end($parts);
    }

    /**
     * Gets contents of a directory.
     * Ignores items that start with '.' or '..'.
     *
     * @param string $dir
     * @return array
     * @throws Exception
     */
    public static function getDirectoryContents(string $dir): array
    {
        if (!file_exists($dir)) throw new Exception("'" . $dir . "' doesn't exist.");
        if (!is_dir($dir)) throw new Exception("'" . $dir . "' is not a directory.");

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
     * @example deleteDirectory("dir") --> deletes contents and directory 'dir'
     * @example deleteDirectory("dir", false) --> deletes only contents of directory 'dir'
     * @example deleteDirectory("dir", false, ["defaultData", "keep.txt"]) --> deletes only contents of directory
     *          'dir', but keeps directory 'defaultData' and file 'keep.txt' and their parent directories
     *
     * @param string $dir
     * @param bool $deleteSelf
     * @param array $exceptions
     * @return void
     * @throws Exception
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

    /**
     * @throws Exception
     */
    private static function deleteDirectoryHelper(string $dir, bool $deleteSelf = true, array $exceptions = [])
    {
        if (!file_exists($dir)) throw new Exception("'" . $dir . "' doesn't exist.");
        if (!is_dir($dir)) throw new Exception("'" . $dir . "' is not a directory.");

        foreach ($exceptions as $exception) {
            if (str_contains($exception, $dir)) {
                $deleteSelf = false;
                break;
            }
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
     * Option for exceptions that should not be copied and whether
     * to delete original directory.
     *
     * @example copyDirectory("<path>/dir1/", "<path>/dir2/") --> copies contents of dir1 to dir2
     * @example copyDirectory("<path>/dir1/", "<path>/dir2/", ["file.txt", "dir1/dir11"]) --> copies contents of dir1 to dir2, except for 'file.txt' and directory 'dir1/dir11'
     * @example copyDirectory("<path>/dir1/", "<path>/dir2/", [], true) --> copies contents of dir1 to dir2 and deletes original directory
     *
     * @param string $dir
     * @param string $copyTo
     * @param array $exceptions
     * @param bool $deleteOriginal
     * @return void
     * @throws Exception
     */
    public static function copyDirectory(string $dir, string $copyTo, array $exceptions = [], bool $deleteOriginal = false)
    {
        if (!Utils::strEndsWith($dir, "/") || !Utils::strEndsWith($copyTo, "/"))
            throw new Exception("Directory paths need to end with '/'.");

        if (!file_exists($dir)) throw new Exception("'" . $dir . "' doesn't exist.");
        if (!is_dir($dir)) throw new Exception("'" . $dir . "' is not a directory.");

        if (in_array(PHP_OS, ["WIN32", "WINNT", "Windows"])) {
            // Change directory separator
            $dir = str_replace("/", "\\", $dir);
            $copyTo = str_replace("/", "\\", $copyTo);
            shell_exec("xcopy " . $dir . " " . $copyTo . " /E /Y");

        } elseif (in_array(PHP_OS, ["Linux", "Unix"]))
            shell_exec("cp -R " . $dir . " " . $copyTo);
        else throw new Exception("Can't copy directory: OS is neither Windows nor Linux based.");

        if (count($exceptions) != 0) {
            foreach ($exceptions as $exception) {
                $exception = $copyTo . "/" . $exception;
                if (file_exists($exception)) {
                    if (is_dir($exception)) self::deleteDirectory($exception);
                    else unlink($exception);
                }
            }
        }

        if ($deleteOriginal) self::deleteDirectory($dir);
    }

    /**
     * Uploads a given file to a given directory. It will create directory
     * if it doesn't exist already.
     *
     * @param string $to
     * @param string $base64
     * @param string $filename
     * @return string
     * @throws Exception
     */
    public static function uploadFile(string $to, string $base64, string $filename): string
    {
        if (!file_exists($to)) mkdir($to, 0777, true);
        if (!is_dir($to)) {
            unlink($to);
            throw new Exception("Can't upload file to '" . $to . "' since it isn't a directory.");
        }

        if (!strpos($filename, ".")) {
            preg_match("/data:(.*?)\/(.*?);base64,/", $base64, $matches);
            $filename .= "." . $matches[2];
        }

        preg_match('/\w/', substr($to, -1), $matches);
        if (count($matches) != 0) $to .= "/";
        $path = $to . $filename;

        $file = base64_decode(preg_replace("/^data:.*\/.*;base64,/i", '', $base64));

        if (self::strStartsWith($base64, "data:image") && self::getBase64ImgSize($base64) > 300) {
            // If image is too big, compress it
            self::compressImage($file, $path, 25);

        } else {
            file_put_contents($path, $file);
        }

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
     * @throws Exception
     */
    public static function deleteFile(string $from, string $filename, bool $deleteIfEmpty = true)
    {
        if (!is_dir($from))
            throw new Exception("Can't delete file from '" . $from . "' since it isn't a directory.");

        preg_match('/\w/', substr($from, -1), $matches);
        if (count($matches) != 0) $from .= "/";
        $path = $from . $filename;

        if (file_exists($path))
            unlink($path);

        // Delete directory if empty
        if (self::getDirectorySize($from) === 0 && $deleteIfEmpty) Utils::deleteDirectory($from);
    }

    /**
     * Gets size of a base64 image.
     *
     * @param string $base64
     * @param string $resultIn
     * @return float|int
     */
    public static function getBase64ImgSize(string $base64, string $resultIn = "KB")
    {
        $size_in_bytes = (int) (strlen(rtrim($base64, '=')) * 3 / 4);
        $size_in_kb = $size_in_bytes / 1024;
        $size_in_mb = $size_in_kb / 1024;

        if ($resultIn == "B") return $size_in_bytes;
        if ($resultIn == "KB") return $size_in_kb;
        if ($resultIn == "MB") return $size_in_mb;

        return $size_in_mb;
    }

    /**
     * Compresses a given image.
     * Quality ranges from 0 (worst quality, smaller file) to 100
     * (best quality, biggest file).
     *
     * @param string $img
     * @param string $to
     * @param int $quality
     * @return void
     */
    public static function compressImage(string $img, string $to, int $quality = 75)
    {
        $img = imagecreatefromstring($img);
        imagejpeg($img, $to, $quality);
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- String Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Checks if a string starts with a given substring.
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function strStartsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if (!$length) return true;
        return substr($haystack, 0, $length) === $needle;
    }

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
     * Removes all whitespace from a string.
     *
     * @param string $str
     * @param string $replace
     * @return string
     */
    public static function trimWhitespace(string $str, string $replace = ""): string
    {
        return preg_replace("/\s+/", $replace, $str);
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

    /**
     * Strips string of non-English characters and/or whitespace.
     *
     * @param string $str
     * @param string $replace
     * @return string
     */
    public static function strip(string $str, string $replace = ""): string
    {
        return self::trimWhitespace(self::swapNonENChars($str), $replace);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Array Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Checks whether a given array is sequential.
     * NOTE: sequential --> [0 => x1, 1 => x2, ...]
     *       associative --> ["key1" => x1, "key2" => x2, ...]
     *
     * @param array $array
     * @return bool
     */
    public static function isSequentialArray(array $array): bool
    {
        if (empty($array)) return false;
        return array_keys($array) === range(0, count($array) - 1);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- CSV ------------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports items from a .csv file.
     * Returns the nr. of items imported.
     * NOTE: headers order must match the order in the file
     *
     * @param array|null $headers
     * @param $import
     * @param string $file
     * @return int
     */
    public static function importFromCSV(array $headers, $import, string $file): int
    {
        $nrItemsImported = 0;
        if (empty($file)) return $nrItemsImported;
        $separator = self::detectSeparator($file);

        $indexes = [];
        foreach ($headers as $i => $header) { $indexes[$header] = $i; }

        // Filter empty lines
        $lines = array_filter(explode("\n", $file), function ($line) { return !empty($line); });

        if (count($lines) > 0) {
            // Check whether 1st line holds headers and ignore them
            $firstLine = array_map('trim', explode($separator, trim($lines[0])));
            if (in_array($headers[0], $firstLine)) array_shift($lines);

            // Import each item
            foreach ($lines as $line) {
                // Parse line manually into each item's param
                // NOTE: this makes sure CSV separators found on info strings don't break the line
                $item = [];
                $opened = false;
                $param = "";
                foreach (str_split(trim($line)) as $char) {
                    if ($char === $separator) {
                        if ($opened) {
                            $param .= $char;
                            continue;
                        }
                        $item[] = $param;
                        $param = "";

                    } else if ($char === "\"") {
                        if (!$opened) $opened = true;
                        else $opened = false;

                    } else $param .= $char;
                }
                $item[] = $param;
                $nrItemsImported += $import($item, $indexes);
            }
        }

        return $nrItemsImported;
    }

    /**
     * Exports items into a .csv file.
     * NOTE: info order must match the headers order
     *
     * @param array $items
     * @param $getInfo
     * @param array|null $headers
     * @return string
     */
    public static function exportToCSV(array $items, $getInfo, array $headers = null): string
    {
        $len = count($items);
        $separator = ",";
        $file = "";

        // Add headers
        if (!empty($headers)) $file .= join($separator, $headers) . "\n";

        // Add each item
        foreach ($items as $i => $item) {
            // Surround string info with ""
            // NOTE: this ensures the line is not broken when there's an CSV separator on the info
            $itemInfo = array_map(function ($info) { return is_string($info) ? "\"$info\"" : $info; }, $getInfo($item));

            $file .= join($separator, $itemInfo);
            if ($i != $len - 1) $file .= "\n";
        }
        return $file;
    }

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


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Validations ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Checks whether a given e-mail is in a valid format.
     *
     * @param string|null $email
     * @return bool
     */
    public static function isValidEmail(?string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        $prefix = explode("@", $email)[0];
        $domain = explode("@", $email)[1];
        if (Utils::strEndsWith($prefix, "-") || str_contains($prefix, "#")) return false;
        foreach (explode(".", $domain) as $part) if (strlen($part) < 2) return false;
        return true;
    }

    /**
     * Checks whether a date is in a given format.
     *
     * @param string|null $date
     * @param string $format
     * @return bool
     */
    public static function isValidDate(?string $date, string $format): bool
    {
        return !!DateTime::createFromFormat($format, $date);
    }

    /**
     * Checks whether a given color is in a valid format.
     *
     * @param string|null $color
     * @param string $format
     * @return bool
     * @throws Exception
     */
    public static function isValidColor(?string $color, string $format): bool
    {
        if ($format == "HEX") $pattern = "/^#[\dabcdef]{6}$/i";
        else if ($format == "RGB") $pattern = "/^RGB\(\b(?:1\d{2}|2[0-4]\d|[1-9]?\d|25[0-5])\b,\s*\b(?:1\d{2}|2[0-4]\d|[1-9]?\d|25[0-5])\b,\s*\b(?:1\d{2}|2[0-4]\d|[1-9]?\d|25[0-5])\b\)$/i";
        else throw new Exception("Color format '" . $format . "' not found.");

        preg_match($pattern, $color, $matches);
        if (!is_string($color) || empty($color) || count($matches) == 0) return false;
        return true;
    }

    /**
     * Checks whether a given version is in a valid format.
     *
     * @param string|null $version
     * @return bool
     */
    public static function isValidVersion(?string $version): bool
    {
        if (is_null($version)) return true;
        preg_match("/^(\d+\.)?(\d+\.)?(\*|\d+)$/", $version, $matches);
        if (!is_string($version) || empty($version) || count($matches) == 0)
            return false;
        return true;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Parsing & Trimming ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses an item coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @example parse item --> parse(["int" => ["id", "course"], "bool" => "isActive"], <item>)
     * @example parse specific field --> parse(["int" => ["id", "course"], "bool" => "isActive"], null, <field>, "id")
     *
     * @param array $values
     * @param array|null $item
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    public static function parse(array $values, array $item = null, $field = null, string $fieldName = null)
    {
        $intValues = $values["int"] ?? [];
        $boolValues = $values["bool"] ?? [];
        $jsonValues = $values["json"] ?? [];

        if ($item) { // parsing an item
            foreach ($values as $type => $params) {
                foreach ($params as $param) {
                    if (isset($item[$param])) {
                        if ($type == "int") $item[$param] = intval($item[$param]);
                        if ($type == "bool") $item[$param] = boolval($item[$param]);
                        if ($type == "json") $item[$param] = json_decode($item[$param], true);
                    }
                }
            }
            return $item;

        } else { // parsing a specific field
            if (in_array($fieldName, $intValues)) return is_numeric($field) ? intval($field) : $field;
            if (in_array($fieldName, $boolValues)) return boolval($field);
            if (in_array($fieldName, $jsonValues)) return json_decode($field, true);
            return $field;
        }
    }

    /**
     * Removes whitespace from the beginning and end of a set of
     * values.
     *
     * @example trim array of values --> trim(["name", "description"], ["name" => " Name ", "description" => "Description"])
     * @example trim multiple values --> trim(["name", "description"], " Name ", "Description", ...)
     *
     * @param mixed ...$values
     * @return void
     */
    public static function trim(array $params, &...$values)
    {
        if (is_array($values[0])) { // array of values
            foreach ($params as $param) {
                if (isset($values[0][$param])) $values[0][$param] = trim($values[0][$param]);
            }

        } else { // multiple values
            foreach ($values as &$value) {
                if (is_string($value)) $value = trim($value);
            }
        }
    }

    /**
     * Makes a string NULL if it is empty.
     *
     * @param string $value
     * @return string|null
     */
    public static function nullify(string $value): ?string
    {
        if (empty(trim($value))) return null;
        return $value;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Versioning -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Compares two versions. Returns:
     *  - -1 if v1 < v2
     *  - +1 if v1 > v2
     *  - 0 if v1 == v2
     *
     * @param string|null $version1
     * @param string|null $version2
     * @return int
     */
    public static function compareVersions(string $version1, string $version2): int
    {
        $v1Parts = self::getVersionParts($version1);
        $v2Parts = self::getVersionParts($version2);

        foreach ($v1Parts as $i => $v1Part) {
            if ($v1Part < $v2Parts[$i]) return -1;
            if ($v1Part > $v2Parts[$i]) return 1;
        }
        return 0;
    }

    /**
     * Parses version to format x.x.x and returns its parts.
     *
     * @param string $version
     * @return array
     */
    private static function getVersionParts(string $version): array
    {
        $parts = array_map('intval', explode(".", $version));
        while (count($parts) != 3) { $parts[] = 0; }
        return $parts;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Re-ordering items ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Updates a given item's position.
     * Option to perform an additional action on items whose
     * position has changed as a consequence.
     *
     * @param int|null $from
     * @param int|null $to
     * @param string $itemTable
     * @param string $orderKey
     * @param $itemId
     * @param array $items
     * @param null $action
     * @return void
     */
    public static function updateItemPosition(?int $from, ?int $to, string $itemTable, string $orderKey, $itemId,
                                              array $items, $action = null)
    {
        if ($to !== $from) {
            if (is_null($from)) self::addItemPosition($to, $itemTable, $orderKey, $itemId, $items, $action);
            else if (is_null($to)) self::deleteItemPosition($from, $itemTable, $orderKey, $itemId, $items, $action);
            else self::editItemPosition($from, $to, $itemTable, $orderKey, $itemId, $items, $action);
        }
    }

    /**
     * Adds a given item's position.
     * Option to perform an additional action on items whose
     * position has changed as a consequence.
     *
     * @param int $to
     * @param string $itemTable
     * @param string $orderKey
     * @param $itemId
     * @param array $items
     * @param null $action
     * @return void
     */
    private static function addItemPosition(int $to, string $itemTable, string $orderKey, $itemId, array $items, $action = null)
    {
        // Filter items that must move down
        $moveDown = array_filter($items, function ($item) use ($itemId, $orderKey, $to) {
            return $item["id"] != $itemId && $item[$orderKey] >= $to;
        });

        // Move items down
        foreach (array_reverse($moveDown) as $item) {
            self::setItemPosition($item["id"], $item[$orderKey] + 1, $itemTable, $orderKey);
            if ($action) $action($item["id"], $item[$orderKey], $item[$orderKey] + 1);
        }

        // Set new item position
        self::setItemPosition($itemId, $to, $itemTable, $orderKey);
    }

    /**
     * Edits a given item's position.
     * Option to perform an additional action on items whose
     * position has changed as a consequence.
     *
     * @param int $from
     * @param int $to
     * @param string $itemTable
     * @param string $orderKey
     * @param $itemId
     * @param array $items
     * @param null $action
     * @return void
     */
    private static function editItemPosition(int $from, int $to, string $itemTable, string $orderKey, $itemId,
                                             array $items, $action = null)
    {
        $direction = $from - $to > 0 ? "up" : "down";

        // Remove item position
        self::setItemPosition($itemId, null, $itemTable, $orderKey);

        // Filter items that must move
        $move = array_filter($items, function ($item) use ($itemId, $direction, $from, $to, $orderKey) {
            if ($item["id"] == $itemId) return false;
            if ($direction == "up") return $item[$orderKey] >= $to && $item[$orderKey] < $from;
            else return $item[$orderKey] > $from && $item[$orderKey] <= $to;
        });

        // Move items
        if ($direction == "up") $move = array_reverse($move);
        foreach ($move as $item) {
            self::setItemPosition($item["id"], $item[$orderKey] + ($direction == "up" ? 1 : -1), $itemTable, $orderKey);
            if ($action) $action($item["id"], $item[$orderKey], $item[$orderKey] + ($direction == "up" ? 1 : -1));
        }

        // Set item position
        self::setItemPosition($itemId, $to, $itemTable, $orderKey);
    }

    /**
     * Deletes a given item's position.
     * Option to perform an additional action on items whose
     * position has changed as a consequence.
     *
     * @param int $from
     * @param string $itemTable
     * @param string $orderKey
     * @param $itemId
     * @param array $items
     * @param null $action
     * @return void
     */
    private static function deleteItemPosition(int $from, string $itemTable, string $orderKey, $itemId, array $items,
                                               $action = null)
    {
        // Remove item position
        self::setItemPosition($itemId, null, $itemTable, $orderKey);

        // Filter items that must move up
        $moveUp = array_filter($items, function ($item) use ($itemId, $orderKey, $from) {
            return $item["id"] != $itemId && $item[$orderKey] > $from;
        });

        // Move items up
        foreach ($moveUp as $item) {
            self::setItemPosition($item["id"], $item[$orderKey] - 1, $itemTable, $orderKey);
            if ($action) $action($item["id"], $item[$orderKey], $item[$orderKey] - 1);
        }
    }

    /**
     * Sets a given item's position on the database.
     *
     * @param $itemId
     * @param int|null $position
     * @param string $table
     * @param string $key
     * @return void
     */
    private static function setItemPosition($itemId, ?int $position, string $table, string $key) {
        Core::database()->update($table, [$key => $position], ["id" => $itemId]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Logging --------------------- ***/
    /*** ---------------------------------------------------- ***/

    const LOGS_SEPARATOR = "================================================================================";

    /**
     * Initializes logging on a given path by creating an empty
     * log file.
     *
     * @param string $path
     * @return void
     */
    public static function initLogging(string $path)
    {
        $logsFile = LOGS_FOLDER . "/" . $path;
        if (!file_exists(LOGS_FOLDER)) mkdir(LOGS_FOLDER, 0777, true);
        if (!file_exists($logsFile)) {
            $parts = explode("/", $path);
            if (count($parts) > 1) {    // directory
                $dir = LOGS_FOLDER . "/" . implode("/", array_slice($parts, 0, -1));
                if (!file_exists($dir)) mkdir( $dir, 0777, true);
            }
        }
        file_put_contents($logsFile, "");
    }

    /**
     * Removes logging on a given path by deleting its log file.
     * @return void
     * @throws Exception
     */
    public static function removeLogging(string $path)
    {
        $parts = explode("/", $path);
        $filename = end($parts);
        if (count($parts) == 1) $dir = LOGS_FOLDER;
        else $dir = LOGS_FOLDER . "/" . implode("/", array_slice($parts, 0, -1));
        self::deleteFile($dir, $filename);
    }

    /**
     * Gets logs on a given path.
     *
     * @param string $path
     * @return string
     */
    public static function getLogs(string $path): string
    {
        $logsFile = LOGS_FOLDER . "/" . $path;
        return file_exists($logsFile) ? file_get_contents($logsFile) : "";
    }

    /**
     * Creates a new log on a given path.
     *
     * @param string $path
     * @param string $message
     * @param string $type
     * @return void
     */
    public static function addLog(string $path, string $message, string $type = "ERROR")
    {
        $sep = self::LOGS_SEPARATOR;
        $header = "[" . date("Y/m/d H:i:s") . "] [$type] : ";
        $log = "\n$sep\n$header$message\n$sep\n\n";

        $logsFile = LOGS_FOLDER . "/" . $path;
        if (!file_exists(LOGS_FOLDER)) mkdir(LOGS_FOLDER, 0777, true);
        if (!file_exists($logsFile)) {
            $parts = explode("/", $path);
            if (count($parts) > 1) {    // directory
                $dir = implode("/", array_slice($parts, 0, -1));
                mkdir(LOGS_FOLDER . "/" . $dir, 0777, true);

            } else file_put_contents($logsFile, "");    // file
        }

        file_put_contents($logsFile, $log, FILE_APPEND);
    }
}
