<?php
/**
 * Created by PhpStorm.
 * User: anton
 * Date: 14/01/2019
 * Time: 10:45
 */

namespace Gam\Controller;

class Search extends Abstracts
{
    
    /**
     * search page
     * @return void
     */
    public static function indexAction()
    {
        static::$_pageClass = 'search-page';

        static::$_templates = [
            'base/header.phtml',
            'contract/list.phtml',
            'base/footer.phtml'
        ];

        if (!($query = static::getRequest('q'))) {
            static::redirectUrl('/');
        }

        $user = static::getModel('user');
        $model = static::getModel('search');

        static::$_responses['contracts'] = $model->getContract(['title' => $query]);

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses = [
                'success' => true,
                'message' => 'ajax request is success.',
                'content' => static::loadLayout($isAjax)
            ];
            static::sendJson(static::$_responses);
        } else {
            static::loadLayout();
        }
    }
}