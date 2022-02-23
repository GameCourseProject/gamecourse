<?php

use GameCourse\Course;

class Utils {
    static function discoverFiles($baseDir, ...$files) {
        $discoveredFiles = array();
        foreach($files as &$filePart) {
            if (strpos($filePart, '//') === 0 || strpos($filePart, 'http') === 0) {
                $discoveredFiles[] = $filePart;
                continue;
            } else if (strpos($filePart, '/') === 0)
                $file = $filePart;
            else
                $file = $baseDir . (strrpos($baseDir, '/') == strlen($baseDir) - 1 ? '' : '/') . $filePart;
            if(is_dir($file)) {
                static::discoverSubFiles($file, $discoveredFiles);
            } else {
                $discoveredFiles[] = $file;
            }
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

    static function camelCase($string) {
        return preg_replace_callback ('/\s(.?)/', function ($matches) { return strtoupper($matches[1]); }, strtolower($string));
    }

    static function checkAndGetPostFromInput() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
            $_POST = json_decode(file_get_contents('php://input'), true);
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            if ($_SERVER['CONTENT_LENGTH'] <= 5000000)
                $GLOBALS['uploadedFile'] = file_get_contents('php://input');
            else {
                throw new Exception('File too big, limit: 5MB', 413);
            }
        }
        if ($_POST == null)
            $_POST = array();
    }

    static function goThroughRoles($roles, $func, &...$data) {
        foreach ($roles as $role) {
            $hasChildren = array_key_exists('children', $role);

            $continue = function(&...$data) use ($role, $func, $hasChildren) {
                if ($hasChildren) {
                    static::goThroughRoles($role['children'], $func, ...$data);
                }
            };
            $func($role, $hasChildren, $continue, ...$data);
        }
    }

    public static function printTrace($die = false, $msg = null, $filter = null) {
        if ($msg != null)
            echo $msg;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        unset($trace[0]);
        if ($filter != null)
            $trace = array_filter($trace, $filter);
        echo '<pre>';
        print_r(array_values($trace));
        echo '</pre>';
        if ($die)
            die();
    }

    static function deleteDirectory($dir, $exceptions = array(), $deleteSelf = true)   // Deletes directory and all contents
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

    /**
     * Transforms a given URL path:
     *  - from absolute to relative
     *  - from relative to absolute
     *
     * @example absolute -> relative
     *  URL: http://localhost/gamecourse/backend/course_data/<courseFolder>/skills/<skillName>/<filename>
     *  NEW URL: skills/<skillName>/<filename>
     *
     * @example relative -> absolute
     *  URL: skills/<skillName>/<filename>
     *  NEW URL: http://localhost/gamecourse/backend/course_data/<courseFolder>/skills/<skillName>/<filename>
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
}
?>
