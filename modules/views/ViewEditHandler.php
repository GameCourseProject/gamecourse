<?php
namespace Modules\Views;

class ViewEditHandler {
    static function breakValue(&$value, &$savePart) {
        $savePart($value);
        $value = $value['pid'];
    }

    static function breakRepeat(&$container, &$savePart, $func) {
        foreach($container as &$child) {
            $func($child, $savePart);
        }
    }

    static function breakRows(&$rows, &$savePart) {
        static::breakRepeat($rows, $savePart, function(&$row) use(&$savePart) {
            foreach($row['values'] as &$cell) {
                static::breakPart($cell['value'], $savePart);
            }
        });
    }

    static function breakPart(&$part, &$savePart) {
        if (array_key_exists('header', $part)) {
            static::breakValue($part['header']['title'], $savePart);
            static::breakValue($part['header']['image'], $savePart);
        }

        if (array_key_exists('children', $part)) {
            static::breakRepeat($part['children'], $savePart, function(&$child) use(&$savePart) {
                static::breakPart($child, $savePart);
            });
        }

        if ($part['type'] == 'value' || $part['type'] == 'image')
            static::breakValue($part, $savePart);
        else if ($part['type'] == 'table') {
            static::breakRows($part['headerRows'], $savePart);
            static::breakRows($part['rows'], $savePart);

            $savePart($part);
            $part = $part['pid'];
        } else {
            $savePart($part);
            $part = $part['pid'];
        }

    }

    static function breakView($view, $parentParts) {
        $brokenView = array(
            'part' => $view['pid'],
            'partlist' => array(
            )
        );
        $partList = &$brokenView['partlist'];

        $savePart = function($part) use(&$partList, $parentParts) {
            unset($part['origin']);
            unset($part['replacements']);

            if (!array_key_exists($part['pid'], $parentParts))
                $partList[$part['pid']] = $part;
        };

        foreach ($view['content'] as &$part) {
            static::breakPart($part, $savePart);
        }

        $savePart($view);
        $brokenView['replacements'] = array_key_exists('replacements', $view) ? $view['replacements'] : array();

        return $brokenView;
    }


    static function putTogetherValue(&$value, &$getPart) {
        if (!is_array($value))
            $value = $getPart($value);
    }

    static function putTogetherRepeat(&$container, &$getPart, $func) {
        foreach($container as &$child) {
            $func($child, $getPart);
        }
    }

    static function putTogetherRows(&$rows, &$getPart) {
        static::putTogetherRepeat($rows, $getPart, function(&$row) use(&$getPart) {
            foreach($row['values'] as &$cell) {
                static::putTogetherPart($cell['value'], $getPart);
            }
        });
    }

    static function putTogetherPart(&$part, &$getPart) {
        if (is_array($part))
            return;
        $part = $getPart($part);

        if (array_key_exists('header', $part)) {
            $header = &$part['header'];
            static::putTogetherValue($header['title'], $getPart);
            static::putTogetherValue($header['image'], $getPart);
        }

        if (array_key_exists('children', $part)) {
            static::putTogetherRepeat($part['children'], $getPart, function(&$child) use(&$getPart) {
                static::putTogetherPart($child, $getPart);
            });
        }

        if ($part['type'] == 'value' || $part['type'] == 'image')
            static::putTogetherValue($part, $getPart);
        else if ($part['type'] == 'table') {
            static::putTogetherRows($part['headerRows'], $getPart);
            static::putTogetherRows($part['rows'], $getPart);
        }
    }

    static function putTogetherView($view, $parentParts) {
        $partList = &$view['partlist'];
        //if ($view[]'replacements'], $view))
         //   $view['replacements'] = array();
        $replacements = &$view['replacements'];

        foreach ($replacements as $part => $replacement)
            $parentParts[$part] = array('pid-point' => $replacement);

        $getPart = function($partId) use ($partList, $parentParts) {
            if (array_key_exists($partId, $parentParts)) {
                $part = &$parentParts[$partId];
                $i = 0;
                while(array_key_exists('pid-point', $part)) {
                    if ($i > 100000)
                        throw new \RuntimeException('LOOP?');
                    $nid = $part['pid-point'];
                    if (array_key_exists($nid, $parentParts)) {
                        $part = &$parentParts[$part['pid-point']];
                    } else if (array_key_exists($nid, $partList)) {
                        $part = &$partList[$part['pid-point']];
                    } else
                        throw new \Exception('Unknown part in pointer: ' . $partId . ' lp=' . $nid);
                    ++$i;
                }
                $part['origin'] = array_key_exists($part['pid'], $partList) ? 'this' : 'other';
                return $part;
            } else if (array_key_exists($partId, $partList)) {
                $part = &$partList[$partId];
                $part['origin'] = 'this';
                return $part;
            } else {
                throw new \Exception('Unknown part: ' . $partId);
            }
        };

        $viewPart = $getPart($view['part']);
        $viewPart['replacements'] = $replacements;
        foreach ($viewPart['content'] as &$part) {
            static::putTogetherPart($part, $getPart);
        }
        return $viewPart;
    }

    static function getRandomPid() {
        return md5(microtime(true) . rand());
    }
}
