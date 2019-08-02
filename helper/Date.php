<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */
namespace Gam\Helper;

class Date {

    /**
     * format datetime
     * @param string
     * @return string
     */
    public static function formatDate($date='', $format='d/m/Y')
    {
        return date($format, strtotime($date));
    }

}