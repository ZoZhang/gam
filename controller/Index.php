<?php
/**
 * Projet Goask MeGam
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */

namespace Gam\Controller;

class Index extends Abstracts
{

    /**
     * Home Default action
     * @throws \Gam\Exception
     */
    public static function indexAction()
    {
        static::$_pageTitle = '问我呗';

        static::$_pageClass = 'page-index';
        static::$_templates = [
            'base/header.phtml',
            'contract/list.phtml',
            'base/footer.phtml'
        ];

        $model = static::getModel('contract');
        static::$_responses['contracts'] = $model->getContract();

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