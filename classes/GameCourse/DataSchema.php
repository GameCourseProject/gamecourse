<?php
namespace GameCourse;

use \Utils as Utils;

class DataSchema {
    const TYPE_COMMON_VALUE = 0;
    const TYPE_ARRAY = 1;
    const TYPE_OBJECT = 2;
    const TYPE_MAP = 3;

    private static $fields = array();

    public static function register($obj) {
        if (array_key_exists('field', $obj)) { // is single field
            static::__register(static::$fields, $obj);
        } else {
            static::__registerMultiple(static::$fields, $obj);
        }
    }

    private static function isFieldSimilar($f1, $f2) {
        $test = $f1['field'] == $f2['field'] && $f1['type'] == $f2['type'];
        if ($test && $f1['type'] == self::TYPE_MAP)
            return static::isFieldSimilar($f1['options']['key'], $f1['options']['key']);
        return $test;
    }

    private static function __registerMultiple(&$base, $fields) {
        foreach ($fields as $field)
            static::__register($base, $field);
    }

    private static function __register(&$base, $field) {
        $name = $field['field'];
        $type = $field['type'];
        $desc = $field['desc'];

        if (is_array($base) && !array_key_exists('field', $base) && array_key_exists($name, $base)) {
            if ($type == self::TYPE_COMMON_VALUE || ($type == self::TYPE_ARRAY && $base[$name]['value']['type'] == self::TYPE_COMMON_VALUE) || !static::isFieldSimilar($field, $base[$name])) {
                Utils::printTrace(true, 'Tried to register field "' . $name . '" already exists.', function($val) {
                    return $val['class'] != __CLASS__;
                });
            }

            switch($type) {
                case self::TYPE_ARRAY:
                    static::__register($base[$name]['value'], $field['value']);
                    break;
                case self::TYPE_OBJECT:
                    static::__registerMultiple($base[$name]['fields'], $field['fields']);
                    break;
                case self::TYPE_MAP:
                    static::__register($base[$name]['options']['value'], $field['options']['value']);
                    break;
            }
            return;
        } else if (is_array($base) && array_key_exists('field', $base)) {
            if (!static::isFieldSimilar($base, $field)) {
                Utils::printTrace(true, 'New field "' . $name . '" is not equal to the existing one.', function($val) {
                    return $val['class'] != __CLASS__;
                });
            }

            switch($type) {
                case self::TYPE_COMMON_VALUE:
                    Utils::printTrace(true, 'Duplicate field' . $name, function($val) {
                        return $val['class'] != __CLASS__;
                    });
                    break;
                case self::TYPE_ARRAY:
                    static::__register($base['value'], $field['value']);
                    break;
                case self::TYPE_OBJECT:
                    static::__registerMultiple($base['fields'], $field['fields']);
                    break;
                case self::TYPE_MAP:
                    static::__register($base['options']['value'], $field['options']['value']);
                    break;
            }
            return;
        }

        $newField = null;
        switch($type) {
            case self::TYPE_COMMON_VALUE:
                $newField = static::makeField($name, $desc, $field['example']);
                break;
            case self::TYPE_ARRAY:
                $newField = static::makeArray($name, $desc, 0);
                static::__register($newField['value'], $field['value']);
                break;
            case self::TYPE_OBJECT:
                $newField = static::makeObject($name, $desc, array(), array_key_exists('$$provider', $field) ? $field['$$provider'] : null);
                static::__registerMultiple($newField['fields'], $field['fields']);
                break;
            case self::TYPE_MAP:
                $newField = static::makeMap($name, $desc, $field['options']['key'], 0, array_key_exists('keys', $field['options']) ? $field['options']['keys'] : null, array_key_exists('$$provider', $field) ? $field['$$provider'] : null);
                static::__register($newField['options']['value'], $field['options']['value']);
                break;
        }

        if (is_array($base))
            $base[$name] = $newField;
        else
            $base = $newField;
    }

    public static function userFields($fields) {
        return static::makeMap('users', 'All Users', static::makeField('id'), static::makeObject('user', 'User', $fields), function() {
            return User::getAll();
        }, function($field, $context, $params, $nextProvider, $wrapped) {
            $users = User::getUserDbWrapper();
            return DataSchema::solveUntilNextProvider($users, $field, $nextProvider, 1, '', $wrapped, $context, $params);
        });
    }

    public static function courseUserFields($fields) {
        return static::makeObject('course', 'Course', array(
                static::makeMap('users', 'All Users', static::makeField('id'), static::makeObject('user', 'User', $fields),
                    function($params) {
                        return Course::getCourse($params['course'])->getUsersIds();
                    },
                    function($field, $context, $params, $nextProvider, $wrapped) {
                        $value = Course::getCourse($params['course'])->getUsers();
                        return DataSchema::solveUntilNextProvider($value, $field[0], $nextProvider, 1, $field[2], $wrapped, $context, $params);
                    }
                )
            ), function($field, $context, $params, $nextProvider, $wrapped) {
                $course = Course::getCourse($params['course'])->getWrapper();
                return DataSchema::solveUntilNextProvider($course, $field, $nextProvider, 1, '', $wrapped, $context, $params);
            }
        );
    }

    public static function courseUserDataFields($fields) {
        return static::courseUserFields(array(static::makeObject('data', 'User data', $fields, function($field, $context, $params, $nextProvider, $wrapped) {
            $user = DataSchema::solveKey('course.users.user', $context, $params);
            $data = Course::getCourse($params['course'])->getUserData($user);
            return DataSchema::solveUntilNextProvider($data, $field[0], $nextProvider, 1, $field[2], $wrapped, $context, $params);
        })));
    }

    public static function courseModuleDataFields($module, $fields) {
        return static::makeObject('moduleData', 'All Module Data', array(
            static::makeObject($module->getId(), 'Module Data for ' . $module->getName(), $fields)
        ), function($field, $context, $params, $nextProvider, $wrapped) {

            $module = preg_split('/[.]/', $field)[1];
            $data = Course::getCourse($params['course'])->getModuleData($module);
            return DataSchema::solveUntilNextProvider($data, $field, $nextProvider, 2, '', $wrapped, $context, $params);
        });
    }

    public static function makeField($field, $desc = null, $example = '') {
        return array(
            'field' => $field,
            'desc' => $desc,
            'example' => $example,
            'type' => self::TYPE_COMMON_VALUE
        );
    }

    public static function makeArray($field, $desc, $value) {
        return array(
            'field' => $field,
            'desc' => $desc,
            'type' => self::TYPE_ARRAY,
            'value' => $value
        );
    }

    public static function makeObject($field, $desc, $fields, $provider = null) {
        $obj = array(
            'field' => $field,
            'desc' => $desc,
            'type' => self::TYPE_OBJECT,
            'fields' => $fields
        );

        if ($provider != null)
            $obj['$$provider'] = $provider;

        return $obj;
    }

    public static function makeMap($field, $desc, $key, $value, $keys = null, $provider = null) {
        $map = array(
            'field' => $field,
            'desc' => $desc,
            'type' => self::TYPE_MAP,
            'options' => array(
                'key' => $key,
                'value' => $value
            )
        );

        if ($keys != null) {
            /*if (is_callable($keys))
                $map['options']['keys'] = $keys();
            else*/
                $map['options']['keys'] = $keys;
        }

        if ($provider != null)
            $map['$$provider'] = $provider;

        return $map;
    }

    public static function getFields($params = array()) {
        $arrCopy = function(&$from, &$to) use (&$arrCopy, $params) {
            $to = array();
            foreach ($from as $k => $v) {
                if ($k === '$$provider')
                    continue;

                if ($k === 'keys' && is_callable($v)) {
                    $to[$k] = $v($params);
                    continue;
                }

                if (!is_null($v) && is_array($v))
                    $arrCopy($v, $to[$k]);
                else if (is_object($v))
                    $to[$k] = clone $v;
                else
                    $to[$k] = $v;
            }
        };

        $fields = array();
        $arrCopy(static::$fields, $fields);
        return $fields;
    }

    public static function getValue($field, $context, $params, $wrapped) {
        $fieldSplit = preg_split('/[.]/', $field);
        $providerChain = array();
        $fieldN = 0;
        $maxN = count($fieldSplit);
        $nextProvider = null;
        $getProviders = function ($fields, $fieldN) use (&$getProviders, &$providerChain, $fieldSplit, $maxN, &$nextProvider) {
            if ($fieldN == 0) {
                $field = $fieldSplit[$fieldN];
                if (array_key_exists($field, $fields)) {
                    $getProviders($fields[$field], $fieldN + 1);
                    return; // nothing else to do here
                } else
                    throw new \Exception('Unknown field: ' . $field);
            } else if (!array_key_exists('type', $fields)) {
                throw new \Exception('This is deep field, should have type..');
            }

            if ($fields['field'] != $fieldSplit[$fieldN-1])
                throw new \Exception('ERROR: Wrong turn somewhere..');
            else if ($fieldN == $maxN)
                return; // OK we got it

            $field = $fieldSplit[$fieldN];

            if ($fields['type'] === static::TYPE_COMMON_VALUE) {
                if ($fieldN != $maxN)
                    throw new \Exception('Unknown field: ' . $field);
            } else if ($fields['type'] === static::TYPE_ARRAY) {
                $providerChain[] = array(-1, static::getArrayAccessor());
                $idx = count($providerChain) - 1;

                if ($nextProvider != null && is_callable($nextProvider))
                    $nextProvider($fieldN);

                $nextProvider = function($val) use($idx, &$providerChain) {
                    $providerChain[$idx][0] = $val - 1;
                };

                if ($fields['value']['field'] == $field)
                    $getProviders($fields['value'], $fieldN + 1);
                else
                    throw new \Exception('Unknown field: ' . $field);
            } else if ($fields['type'] === static::TYPE_OBJECT) {
                if (array_key_exists('$$provider', $fields)) {
                    $providerChain[] = array(-1, $fields['$$provider']);
                    $idx = count($providerChain) - 1;

                    if ($nextProvider != null && is_callable($nextProvider))
                        $nextProvider($fieldN);

                    $nextProvider = function($val) use($idx, &$providerChain) {
                        $providerChain[$idx][0] = $val - 1;
                    };
                }

                if (array_key_exists($field, $fields['fields']))
                    $getProviders($fields['fields'][$field], $fieldN + 1);
                else
                    throw new \Exception('Unknown field: ' . $field);
            } else if ($fields['type'] === static::TYPE_MAP) {
                if (array_key_exists('$$provider', $fields)) {
                    $providerChain[] = array(-1, $fields['$$provider']);
                    $idx = count($providerChain) - 1;

                    if ($nextProvider != null && is_callable($nextProvider))
                        $nextProvider($fieldN);

                    $nextProvider = function($val) use($idx, &$providerChain) {
                        $providerChain[$idx][0] = $val - 1;
                    };
                }

                if ($fields['options']['key']['field'] == $field)
                    $getProviders($fields['options']['key'], $fieldN + 1);
                else if ($fields['options']['value']['field'] == $field)
                    $getProviders($fields['options']['value'], $fieldN + 1);
                else
                    throw new \Exception('Unknown field: ' . $field);
            } else
                throw new \Exception('Unknown field type ' . $fields['type']);
        };
        $getProviders(static::$fields, $fieldN);
        $lastProvider = 0;

        $value = array_reduce($providerChain, function($field, $provider) use (&$context, $params, &$lastProvider, $wrapped) {
            $ret = $provider[1]($field, $context, $params, $provider[0] - $lastProvider, $wrapped);
            $lastProvider = $provider[0];
            return $ret;
        }, $field);
        return $value;
    }

    private static function solveValue($field, $context, $params, $previousValue = null, $soFar = null, $fields = null) {
        if ($fields == null)
            $fields = static::$fields;
        $fieldSplit = preg_split('/[.]/', $field);
        $providerChain = array();
        $maxN = count($fieldSplit);
        $nextProvider = null;
        $getProviders = function ($fields, $fieldCurr, $fieldNext) use (&$getProviders, &$providerChain, $fieldSplit, $maxN, &$nextProvider) {
            if ($fieldNext == 0) {
                $field = $fieldSplit[$fieldCurr];

                if (!array_key_exists('field', $fields)) { // we are on the level 0 of the db
                    if (array_key_exists($field, $fields)) {
                        return $getProviders($fields[$field], $fieldCurr, $fieldNext + 1);
                    } else
                        throw new \Exception('Unknown field: ' . $field);
                } else { // we on a continuation
                    if ($fields['type'] === static::TYPE_COMMON_VALUE) {
                        // nothing here..
                    } else if ($fields['type'] === static::TYPE_ARRAY) {
                        if ($fields['value']['field'] == $field)
                            return $getProviders($fields['value'], $fieldCurr, $fieldNext + 1);
                    } else if ($fields['type'] === static::TYPE_OBJECT) {
                        if (array_key_exists($field, $fields['fields']))
                            return $getProviders($fields['fields'][$field], $fieldCurr, $fieldNext + 1);
                    } else if ($fields['type'] === static::TYPE_MAP) {
                        if ($fields['options']['key']['field'] == $field)
                            return $getProviders($fields['options']['key'], $fieldCurr, $fieldNext + 1);
                        else if ($fields['options']['value']['field'] == $field)
                            return $getProviders($fields['options']['value'], $fieldCurr, $fieldNext + 1);
                    }
                    throw new \Exception('Unknown field: ' . $field);
                }
            }

            if (!array_key_exists('type', $fields)) {
                throw new \Exception('This is deep field, should have type..');
            }

            if ($fieldCurr == $maxN)
                throw new \Exception('Too much?');

            $field = $fieldSplit[$fieldCurr];
            $nextField = ($maxN > $fieldNext) ? $fieldSplit[$fieldNext] : null;

            if ($fieldCurr > 0 && $fields['field'] != $field)
                throw new \Exception('ERROR: Wrong turn somewhere..');

            if ($fields['type'] === static::TYPE_COMMON_VALUE) {
                // ok
                if ($nextField != null)
                    throw new \Exception('Unknown field: ' . $nextField);
            } else if ($fields['type'] === static::TYPE_ARRAY) {
                /*$providerChain[] = array(-1, static::getArrayAccessor());
                $idx = count($providerChain) - 1;

                if ($nextProvider != null && is_callable($nextProvider))
                    $nextProvider($fieldCurr);

                $nextProvider = function($val) use($idx, &$providerChain) {
                    $providerChain[$idx][0] = $val;
                };*/

                if ($nextField != null) {
                    if ($fields['value']['field'] == $nextField)
                        return $getProviders($fields['value'], $fieldCurr + 1, $fieldNext + 1);
                    else
                        throw new \Exception('Unknown field: ' . $nextField);
                } else
                    return $fields;
            } else if ($fields['type'] === static::TYPE_OBJECT) {
                if (array_key_exists('$$provider', $fields)) {
                    $providerChain[] = array(-1, $fields['$$provider']);
                    $idx = count($providerChain) - 1;

                    if ($nextProvider != null && is_callable($nextProvider))
                        $nextProvider($fieldCurr);

                    $nextProvider = function($val) use($idx, &$providerChain) {
                        $providerChain[$idx][0] = $val;
                    };
                }

                if ($nextField != null) {
                    if (array_key_exists($nextField, $fields['fields']))
                        return $getProviders($fields['fields'][$nextField], $fieldCurr + 1, $fieldNext + 1);
                    else
                        throw new \Exception('Unknown field: ' . $nextField);
                } else
                    return $fields;
            } else if ($fields['type'] === static::TYPE_MAP) {
                if (array_key_exists('$$provider', $fields)) {
                    $providerChain[] = array(-1, $fields['$$provider']);
                    $idx = count($providerChain) - 1;

                    if ($nextProvider != null && is_callable($nextProvider))
                        $nextProvider($fieldCurr);

                    $nextProvider = function($val) use($idx, &$providerChain) {
                        $providerChain[$idx][0] = $val;
                    };
                }

                if ($nextField != null) {
                    if ($fields['options']['key']['field'] == $nextField)
                        return $getProviders($fields['options']['key'], $fieldCurr + 1, $fieldNext + 1);
                    else if ($fields['options']['value']['field'] == $nextField)
                        return $getProviders($fields['options']['value'], $fieldCurr + 1, $fieldNext + 1);
                    else
                        throw new \Exception('Unknown field: ' . $nextField);
                } else
                    return $fields;
            } else
                throw new \Exception('Unknown field type ' . $fields['type']);
        };
        $nextFields = $getProviders($fields, 0, 0);
        $lastProvider = 0;

        $next = $field;
        if ($previousValue != null)
            $next = array($field, $previousValue, $soFar);

        if (count($providerChain) > 0) {
            $value = array_reduce($providerChain, function($field, $provider) use (&$context, $params, &$lastProvider) {
                $ret = $provider[1]($field, $context, $params, $provider[0] - $lastProvider, true);
                return $ret;
            }, $next);
            if (!is_null($value) && is_array($value))
                $value = static::solveUntilNextProvider($value[1], $value[0], -1, 0, $value[2], true, $context, $params);
        } else
            $value = static::solveUntilNextProvider($previousValue, $field, -1, 0, $soFar, true, $context, $params);
        return array($value, $nextFields);
    }

    private static function getContinuation($params, $value, $fields, $soFar, $staticWrapped, $oldContext) {
        return function($path, $pathContext, $key = null) use ($staticWrapped, $value, $fields, $soFar, $params, $oldContext) {
            if ($key !== null) {
                if ($fields['type'] === static::TYPE_MAP)
                    $path = $fields['options']['value']['field'];
                else if ($fields['type'] === static::TYPE_ARRAY)
                    $path = $fields['value']['field'];
                else
                    throw new \Exception('Err...');
                $pathContext = array($path => $key);
            }

            $trueSoFar = $soFar . ($soFar != '' ? '.' : '');
            $context = array();
            foreach($pathContext as $k => $v)
                $context[$trueSoFar . $k] = (string)$v;

            foreach($oldContext as $k => $v)
                $context[$k] = $v;

            list($valueRet, $fields) = static::solveValue($path, $context, $params, $value, $soFar, $fields);
            if ($staticWrapped)
                $valueRet = new \ValueWrapper($valueRet->getValue());
            return new DataRetrieverContinuation($valueRet, static::getContinuation($params, $valueRet, $fields, $trueSoFar . $path, $staticWrapped, $context));
        };
    }

    public static function getValueWithContinuation($path, $pathContext, $params, $staticWrapped = false) {
        $cont = static::getContinuation($params, null, null, null, $staticWrapped, array());
        return $cont($path, $pathContext);
    }

    public static function solveKey($key, $context, $params) {
        $keyValue = $context[$key];
        if ($context[$key][0] == '%') {
            $param = substr($keyValue, 1);
            if (array_key_exists($param, $params))
                $keyValue = $params[$param];
            else
                throw new \Exception('Expected parameter ' . $param);
        }
        return $keyValue;
    }

    public static function solveUntilNextProvider($value, $field, $nextProvider, $howManySolved, $soFar, $wrapped, $context, $params) {
        $fieldTokens = preg_split('/[.]/', $field);
        $trueSoFar = (($soFar != '') ? ($soFar . '.') : $soFar);

        if ($nextProvider < 0) {
            $n = count($fieldTokens);
            if ($n - $howManySolved != 0) {
                if ($howManySolved > 0)
                    $trueSoFar .= join('.', array_slice($fieldTokens, 0, $howManySolved)) . '.';
                $left = array_slice($fieldTokens, $howManySolved);
                foreach ($left as $t) {
                    if (array_key_exists($trueSoFar . $t, $context))
                        $value = $value->getWrapped(DataSchema::solveKey($trueSoFar . $t, $context, $params));
                    else
                        $value = $value->getWrapped($t);

                    $trueSoFar .= $t . '.';
                }
            }

            if ($wrapped)
                return $value;
            return $value->getValue();
        } else {
            if ($nextProvider - $howManySolved != 0) {
                if ($howManySolved > 0)
                    $trueSoFar .= join('.', array_slice($fieldTokens, 0, $howManySolved)) . '.';
                $left = array_slice($fieldTokens, $howManySolved, $nextProvider - $howManySolved);

                foreach ($left as $t) {
                    if (array_key_exists($trueSoFar . $t, $context))
                        $value = $value->getWrapped(DataSchema::solveKey($trueSoFar . $t, $context, $params));
                    else
                        $value = $value->getWrapped($t);

                    $trueSoFar .= $t . '.';
                }
            }

            return array(join('.', array_slice($fieldTokens, $nextProvider)), $value, (($soFar != '') ? ($soFar . '.') : $soFar) . join('.', array_slice($fieldTokens, 0, $nextProvider)));
        }
    }
}
?>
