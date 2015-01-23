<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Hopeter1018\DeveloperTool;

/**
 * Description of NetbeansHint
 *
 * @version $id$
 * @author peter.ho
 */
class NetbeansHint
{


// <editor-fold defaultstate="collapsed" desc="Json Posted Hintings">

    private static function getPropertyWithDoc($key, $type)
    {
        return <<<PROP
        /** @var {$type} */
        public \${$key};

PROP;
    }

    /**
     * Sample:
     * <ul>
     * <li>\Zms5\DeveloperTools\NetbeansHintingGenerator::jsonToClasses($data, dirname(__CLASS__));
     * <li>\Zms5\DeveloperTools\NetbeansHintingGenerator::jsonToClasses($data, 'Frontend\Abc');
     * </ul>
     * @param type $data
     * @param type $className
     */
    public static function jsonToClasses($data, $className)
    {
        $base = APP_SYSTEM_STORAGE . "netbeanshinting/jsonPosted/" . str_replace("\\", "/", $className);
        ! is_dir(dirname($base)) and mkdir(dirname($base), 0777, true);
        is_file($base . ".php") and unlink($base . ".php");
        file_put_contents(
            $base . ".php",
            "<?php

die('Netbean hintings');

" . static::getHintClassBody($data, $className)
        );
    }

    const JSONCLASS_NAMESPACE = '\NbHints\\';
    private static function getHintClassBody($data, $className)
    {
        $ns = substr($className, 0, strrpos($className, '\\'));
        $class = substr($className, strrpos($className, '\\') + 1);
        $subClasses = array();
        $properties = array(
            <<<PHP

namespace NbHints\\{$ns} {

    /**
     * Description of {$class}
     *
     * @author peter.ho
     */
    class {$class}
    {

PHP
        );
        foreach($data as $key => $value) {
            if (is_array($value))
            {
                $type = gettype($value[0]);
                if ($type !== 'object') {
                    $properties[] = static::getPropertyWithDoc($key, $type . "[]");
                } else {
                    $subClasses[] = static::getHintClassBody($value[0], $className . "\\" . $key);
                    $properties[] = static::getPropertyWithDoc($key, static::JSONCLASS_NAMESPACE . $className . "\\" . $key . "[]");
                }
            } elseif (is_object($value)) {
                $subClasses[] = static::getHintClassBody($value, $className . "\\" . $key);
                $properties[] = static::getPropertyWithDoc($key, static::JSONCLASS_NAMESPACE . $className . "\\" . $key);
            } else {
                $type = gettype($value);
                $properties[] = static::getPropertyWithDoc($key, $type);
            }
        }
        $properties[] = "\r\n    }\r\n\r\n}\r\n";

        return implode($properties) . implode($subClasses);
    }

// </editor-fold>

}
