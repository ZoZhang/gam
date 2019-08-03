<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */
namespace Gam\Helper;

use \Gam\Controller\Abstracts;

class Core {

    /**
     * get web site base url + path info by AbstractsController
     * @param string $path
     * @return string
     */
    public static function getUrl($path='')
    {
        return Abstracts::getUrl($path);
    }

    /**
     * Getter an model
     * @param string name model
     * @return object
     */
    public static function getModel($name)
    {
        $className = '\\Gam\\Model\\'. ucfirst($name) . 'Model';

        if (!class_exists($className)) {
            throw new Exception("Warning: {$className} is not class");
        }

        return new $className;
    }

    /**
     * get user data after login
     *
     * @return array|boolean
     */
    public static function getCurrentUser()
    {
        if (isset($_SESSION['current_user'])) {
            return $_SESSION['current_user'];
        }
        return false;
    }

    /**
     * logout user
     *
     * @return boolean
     */
    public static function logoutCurrentUser()
    {
        if (isset($_SESSION['current_user'])) {
            unset($_SESSION['current_user']);
        }

        return true;
    }


    /*
     * Genere key activation
     * @return string
     */
    public static function getActivationKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

}