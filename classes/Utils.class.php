<?php
use SmartBoards\Settings as Settings;

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

    static function createBase() {
        return (array_key_exists('HTTPS', $_SERVER) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/'. BASE . '/';
    }

    static function goThroughRoles($roles, $func, &...$data) {
        foreach ($roles as $role) {
            $hasChildren = array_key_exists('children', $role);

            $continue = function(&...$data) use ($role, $func, $hasChildren) {
                if ($hasChildren) {
                    static::goThroughRoles($role['children'], $func, ...$data);
                }
            };

            $func($role['name'], $hasChildren, $continue, ...$data);
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
}
?>
