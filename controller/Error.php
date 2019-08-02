<?php
/**
 * Projet Goask MeGam
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */

namespace Gam\Controller;

class Error extends Abstracts
{
    /**
     * @var array default template files
     */
    protected static $_templates = [
        'base/header.phtml',
        'errors/404.phtml',
        'base/footer.phtml'
    ];

    /**
     * Error Default action
     * @throws \Gam\Exception
     */
    public static function indexAction()
    {
        static::$_pageClass = 'errors-noroute';

        static::loadLayout();
    }
}