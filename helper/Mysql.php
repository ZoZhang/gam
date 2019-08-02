<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */

namespace Gam\Helper;

class Mysql {
    use \Gam\Config;

    private static $_stmt = null;

    private static $_connect = null;

    private static function connect()
    {
        if (is_null(self::$_connect)) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8;port=%s',
                self::getConfig('db','host'),
                self::getConfig('db','name'),
                self::getConfig('db','port') ?? '3306'
            );
            self::$_connect = new \PDO($dsn, self::getConfig('db','user'), self::getConfig('db','pass'));
            self::$_connect->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$_connect->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        }
        return self::$_connect;
    }

    /**
     * PDO Prepare query sql
     * @param string $query
     */
    private static function prepare($query='')
    {
        if (!$query) {
            throw new \Exception('Plese check your sql.');
        }

        self::$_stmt = self::connect()->prepare($query);
    }

    private static function query($options=[])
    {
        $_res = null;
        if (!isset($options['parametes'])) {
            $_res = self::$_stmt->execute();
        } else {
            $_res = self::$_stmt->execute($options['parametes']);
        }

        return $_res;
    }

    private static function fetch()
    {
        $data = [];

        if (self::$_stmt->rowCount()) {
            self::$_stmt->setFetchMode(\PDO::FETCH_OBJ);
            $data = self::$_stmt->fetchAll();
        }
        return $data;
    }

    /**
     *  Genere options data en SQL
     *@return string
     */
    public static function getSQL($options=[])
    {
        $sql = '';

        if (!isset($options['table'])) {
            throw new \PDOException('Pleles check your arguments.');
        }

        if (!isset($options['fields'])){
            $options['fields'] = '*';
        }

        if (!isset($options['operation'])) {

            if (is_array($options['fields'])) {
                $options['fields'] = implode($options['fields'],',');
            }

            $sql .= "SELECT {$options['fields']} FROM {$options['table']}";

            if (isset($options['left_join']) || isset($options['right_join'])) {

                    $indexJoin = 1;
                    $joinsSql = [];

                    if (isset($options['left_join'])) {
                        $joinsSql['LEFT'] = $options['left_join'];
                    }

                    if (isset($options['right_join'])) {
                        $joinsSql['RIGHT'] = $options['right_join'];
                    }

                    foreach ($joinsSql as $join_operation => $join_item) {

                        if (!count($join_item)) continue;

                        if ($indexJoin > 1) {
                            $sql .= ' AND ';
                        }

                        foreach ($join_item as $join_table => $join_condition) {
                            $sql .= " {$join_operation} JOIN {$join_table} ON {$join_condition} ";
                        }

                        $indexJoin++;

                    }
                }

            if (isset($options['where'])) {
                $sql .= ' WHERE ' . $options['where'];
            }

            if (isset($options['order'])) {
                $sql .= ' ORDER BY ' . $options['order'];
            }

            if (isset($options['group_by'])) {
                $sql .= ' GROUP BY ' . $options['group_by'];
            }

        } else {
            switch ($options['operation']) {
                case 'delete':
                    $sql .= "DELETE FROM {$options['table']}";
                    break;
                case 'update':
                    $sql .= "UPDATE {$options['table']}";
                    break;
                case 'insert':
                    $sql .= "INSERT INTO {$options['table']}";
                    break;
            }

            if (!in_array($options['operation'], ['insert', 'update'])) {

                

            } else if ('insert' == $options['operation']) {  //insert operation

                if (!isset($options['fields']) || !count($options['fields'])) {
                    throw new \PDOException('Pleles check your arguments.');
                }

                $fieldsKeys = implode(',', array_keys($options['fields']));

                $sql .= " ({$fieldsKeys}) ";
                $sql .= ' VALUES ( ';

                foreach ($options['fields'] as $key => $val) {
                    $sql .= self::connect()->quote($val) . ',';
                }

                $sql = substr($sql, 0, -1);

                $sql .= ')';

            } else if ('update' == $options['operation']) { //update operation

                if (!isset($options['fields']) || !count($options['fields'])) {
                    throw new \PDOException('Pleles check your arguments.');
                }

                $sql .= ' SET ';

                foreach ($options['fields'] as $key => $val) {
                    $sql .= $key . '=' . self::connect()->quote($val) . ',';
                }

                $sql = substr($sql, 0, -1);
            }
        }

        if (isset($options['operation']) && in_array($options['operation'], ['delete', 'update'])) {
            if (isset($options['where'])) {
                $sql .= ' WHERE ' . $options['where'];
            }
        }
        return $sql;
    }

    /**
     * Get data by options.
     * @return array
     */
    public static function getData($options=[])
    {
        try {
            $res = false;
            $data = [];
            $sql = self::getSQL($options);

            self::prepare($sql);

            $res = self::query($options);
            if (!isset($options['operation'])) {
                $data = self::fetch();
                return $data;
            }
        } catch (\PDOException $e) {
            throw new Exception("Errors: ". $e->getMessage());
        }
        return $res;
    }

    /**
     * Insert data to database.
     * @param array $options
     */
    public static function insertData($options=[])
    {
        try {
            $res = false;
            $sql = self::getSQL($options);
            self::connect()->exec($sql);
            $res = self::getLastInserId();
        } catch (\PDOException $e) {
            self::connect()->rollback();
            throw new Exception("Errors: ". $e->getMessage());
        }
        return $res;
    }

    /**
     * Function to remove something from a database
     *
     * @param array $options
     * @return int
     * @throws Exception
     */
    public static function deleteData($options=[])
    {
        try {
            $res = false;
            $sql = self::getSQL($options);
            self::prepare($sql);
            $res = self::query($options);
        } catch (\PDOException $e) {
            throw new Exception("Errors: ". $e->getMessage());
        }
        return $res;
    }

    /** Update data to database.
     * @param array $options
     */
    public static function updateData($options=[])
    {
        try {
            $res = false;
            $sql = self::getSQL($options);
            self::prepare($sql);
            $res =  self::query($options);
        } catch (\PDOException $e) {
            throw new Exception("Errors: ". $e->getMessage());
        }

        return $res;
    }

    /**
     * get last insert id
     * @return bool|mixed
     * @throws Exception
     */
    public static function getLastInserId()
    {
        try {
            $lastId = false;

            $stmt = self::connect()->query("SELECT LAST_INSERT_ID()");
            $lastId = $stmt->fetchColumn();

        } catch (\PDOException $e) {
            throw new Exception("Errors: ". $e->getMessage());
        }

        return $lastId;

    }
}