<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */
namespace Gam\Model;

use \Gam\Helper\Mysql;

abstract class Abstracts {
    /**
     * Get data by database
     * @param array
     * @return object
     */
    public function getData($options=[])
    {
       return Mysql::getData($options);
    }

    /**
     * Insert data to database
     * @param array
     * @return object
     */
    public function insertData($options=[])
    {
        return Mysql::insertData($options);
    }

    /**
     * Remove from DB
     *
     * @param array $options
     * @return bool
     * @throws Exception
     */
    public function deleteData($options=[])
    {
        return Mysql::deleteData($options);
    }
    
    /**
     * Update data to database
     *
     * @param array
     * @return bool
     */
    public function updateData($options=[])
    {
        return Mysql::updateData($options);
    }

    /*
     * Get paramete by url
     * @return string|boolean|int
     */
    public function getRequest($name='',$default='')
    {
        return AbstractsController::getRequest($name, $default);
    }
}