<?php
/**
 * Created by PhpStorm.
 * User: anton
 * Date: 14/01/2019
 * Time: 10:45
 */

namespace Gam\Controller;

class Contract extends Abstracts
{
    /**
     * Contract list page
     * @return void
     */
    public static function createAction()
    {
        static::$_pageClass = 'contact-list';
        static::$_templates = [
            'base/header.phtml',
            'contract/create.phtml',
            'base/footer.phtml'
        ];

        //create contract
        if ($data = static::getRequest('contract')) {
            static::$_responses['success'] = false;
            static::$_responses['message'] = '发布失败，请重试';

            if (!static::$_responses['current_user']) {
                static::$_responses['message'] = '请登陆后，重新发布';
                static::redirectUrl('user/login');
            }

            $model = static::getModel('contract');
            static::$_responses = $model->create($data);
        }

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses = [
                'success' => true,
                'message' => 'ajax request is success.',
                'content' => static::loadLayout($isAjax)
            ];
            static::sendJson(static::$_responses);
        } else {

            if (isset(static::$_responses['redirect_url'])) {
                static::redirectUrl(static::$_responses);
            } else {
                static::loadLayout();
            }
        }
    }

    /**
     * Contract index page
     * @return void
     */
    public static function indexAction()
    {
        static::redirectUrl('contract/list');
    }

    /**
     * Contract list page
     * @return void
     */
    public static function viewAction()
    {
        static::$_pageClass = 'contact-view';
        static::$_templates = [
            'base/header.phtml',
            'contract/view.phtml',
            'base/footer.phtml'
        ];

        $id = static::getRequest('id');

        static::$_responses['success'] = true;

        //view contract
        if (!$id) {
            static::$_responses['message'] = '该任务不存在';
            static::redirectUrl('contract/list');
        }

        $user = static::getModel('user');
        $model = static::getModel('contract');

        static::$_responses['contract'] = $model->getContract(['id' => $id]);
        static::$_responses['announcer'] = $user->getOne(static::$_responses['contract'][0]->customer_id);

        if (static::$_responses['contract']{0}->delegation_id){
            $delegation = $user->getOne(static::$_responses['contract'][0]->delegation_id);
            static::$_responses['contract']{0}->delegation_id = $delegation{0}->username;
        }

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses = [
                'success' => true,
                'message' => 'ajax request is success.',
                'content' => static::loadLayout($isAjax)
            ];
            static::sendJson(static::$_responses);
        } else {

            if (isset(static::$_responses['redirect_url'])) {
                static::redirectUrl(static::$_responses);
            } else {
                static::loadLayout();
            }
        }
    }

    /**
     * Contract list page
     * @return void
     */
    public static function listAction()
    {
        static::$_pageClass = 'contact-list';
        static::$_templates = [
            'base/header.phtml',
            'contract/list.phtml',
            'base/footer.phtml'
        ];

        $parametes = [];
        $model = static::getModel('contract');

        if(isset(static::$_responses['current_user'])) {

            if (static::$_responses['current_user']['type'] == 'enterprise') {
                $parametes= [
                    'customer_id' => static::$_responses['current_user']['id']
                ];
            } else {
                $parametes= [
                    'delegation_id' => static::$_responses['current_user']['id']
                ];
            }
        }

        static::$_responses['contracts'] = $model->getContract($parametes);

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

    /**
     * Contract accpet page
     * @return void
     */
    public static function acceptAction()
    {
        static::$_pageClass = 'contact-accept';
        static::$_templates = [
            'base/header.phtml',
            'contract/view.phtml',
            'base/footer.phtml'
        ];

        $id = static::getRequest('id');

        static::$_responses['success'] = false;

        if (!isset(static::$_responses['current_user'])) {
            static::$_responses['message'] = '请先登陆';
            static::redirectUrl('user/login');
        }

        if (!$id) {
            static::$_responses['message'] = '该任务不存在';
            static::redirectUrl('contract/list');
        }

        $user = static::getModel('user');
        $model = static::getModel('contract');

        static::$_responses = $model->delegation(['id' => $id]);

        //static::$_responses['announcer'] = $user->getOne(static::$_responses['contract'][0]->customer_id);

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses = [
                'success' => true,
                'message' => 'ajax request is success.',
                'content' => static::loadLayout($isAjax)
            ];
            static::sendJson(static::$_responses);
        } else {

            if (isset(static::$_responses['redirect_url'])) {
                static::redirectUrl(static::$_responses);
            } else {
                static::loadLayout();
            }
        }
    }

    /**
     * Contract finished page
     * @return void
     */
    public static function finishAction()
    {
        static::$_pageClass = 'contact-finish';
        static::$_templates = [
            'base/header.phtml',
            'contract/view.phtml',
            'base/footer.phtml'
        ];

        $id = static::getRequest('id');

        static::$_responses['success'] = false;

        if (!isset(static::$_responses['current_user'])) {
            static::$_responses['message'] = '请先登陆';
            static::redirectUrl('user/login');
        }

        if (!$id) {
            static::$_responses['message'] = '该任务不存在';
            static::redirectUrl('contract/list');
        }

        $user = static::getModel('user');
        $model = static::getModel('contract');

        $contract = $model->getContract(['id' => $id]);
        $delegationUser = $user->getOne($contract{0}->delegation_id);

        static::$_responses = $model->finish([
            'id' => $id,
            'byid' => $delegationUser{0}->byid,
            'password' => $delegationUser{0}->password,
        ]);

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses = [
                'success' => true,
                'message' => 'ajax request is success.',
                'content' => static::loadLayout($isAjax)
            ];
            static::sendJson(static::$_responses);
        } else {

            if (isset(static::$_responses['redirect_url'])) {
                static::redirectUrl(static::$_responses);
            } else {
                static::loadLayout();
            }
        }
    }

}