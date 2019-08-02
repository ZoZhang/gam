<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */

namespace Gam;

include_once './vendor.php';

use \Gam\Helper\Exception;
use \Gam\Controller\Abstracts;

class Bootstrap {

    /*
     * Start application
     */
    public static function run()
    {
        try {

            //Dispatche les pages.
            Abstracts::dispatche();

        } catch (Exception $e) {

            if (DEBUG) {
                print_r($e->getMessage() . PHP_EOL);
            }

            Exception::logger($e->getMessage(), 1);
        } catch (\Exception $e) {
            Exception::logger($e->getMessage(), 0);
        }
    }
}

\Gam\Bootstrap::run();
