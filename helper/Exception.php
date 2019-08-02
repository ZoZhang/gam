<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */

namespace Gam\Helper;

class Exception extends \Exception {

    protected static $_levels = [
        '0' => ['type'=> 3, 'file'=> 'errors.log'],
        '1' => ['type'=> 3, 'file'=> 'exception.log'],
    ];

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Write message to log file
     * @param $message
     * @param $level
     */
    public static function logger($message, $level='0')
    {
        if (!is_dir(LOG_PATH) && !is_writeable(LOG_PATH)) {
            throw new \Exception("Plese check your permission direcotry.");
        }

        if (!is_dir(LOG_PATH)) {
            @mkdir(LOG_PATH);
        }
        $prefix = '['.date('Y-m-d H:i:s').'] ';
        error_log($prefix . $message . PHP_EOL , self::$_levels[$level]['type'],LOG_PATH . DS . self::$_levels[$level]['file']);
    }

}


