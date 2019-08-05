<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */
namespace Shell;

define('BASE_PATH', dirname(dirname(__FILE__)));

include_once BASE_PATH .'/vendor.php';

class Cron {

    protected static $_settings = [
        '\Gam\Model\Contract' => 'pull'
    ];

    public static function exec()
    {
        if (!count(self::$_settings)) {
            return false;
        }

        foreach(self::$_settings as $class => $fuction) {

            $object = new $class;

            if (method_exists($object, $fuction)) {
                $object->$fuction();
            }
        }
    }

}

Cron::exec();

