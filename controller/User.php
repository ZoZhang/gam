<?php
/**
 * Projet Goask MeGam
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */

namespace Gam\Controller;

use \Gam\Helper\Core;

class User extends Abstracts
{
    /**
     * @var array page styles
     */
    protected static $_styles = [
        'css/user.css'
    ];

    /**
     * @var array page javascript
     */
    protected static $_javaScripts = [
        'js/user.js'
    ];

    /**
     * user page default action
     *
     * @return string content html
     */
    public static function indexAction()
    {
        // redirect to user register
        return static::registerAction();
    }

    /**
     * user logout page
     *
     * @return string content html
     */
    public static function logoutAction()
    {
        Core::logoutCurrentUser();

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses['success'] = true;
            static::$_responses['redirect_url'] = '/';
            static::sendJson(static::$_responses);
        } else {
            static::redirectUrl('/');
        }
    }

    /**
     * user register page
     *
     * @return string content html
     */
    public static function registerAction()
    {
        static::$_pageTitle = '用户注册';

        static::$_pageClass = 'user-register';

        static::$_templates = [
            'base/header.phtml',
            'user/register.phtml',
            'base/footer.phtml'
        ];

        // register for form
        if ($data = static::getRequest('register')) {
            $model = static::getModel('user');
            static::$_responses = $model->register($data);
        }

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses['content'] = static::loadLayout($isAjax);
            static::sendJson(static::$_responses);
        } else {
            if (isset(static::$_responses['current_user'])) {

                if(!static::$_responses['current_user']['verified']){
                    static::redirectUrl('user/active');
                } else {
                    static::redirectUrl(static::$_responses);
                }
            } else {

                if (isset(static::$_responses['redirect_url'])) {
                    static::redirectUrl(static::$_responses);
                } else {
                    static::loadLayout();
                }
            }
        }
    }

    /**
     * user active page
     *
     * @return string content html
     */
    public static function activeAction()
    {
        static::$_pageClass = 'user-activation';

        static::$_pageTitle = '用户激活';

        static::$_templates = [
            'base/header.phtml',
            'user/activation.phtml',
            'base/footer.phtml'
        ];

        // default activation page parameters

        if ($email = static::getRequest('email')) {
            static::$_responses['email'] = $email;
        }

        if ($username = static::getRequest('username')) {
            static::$_responses['username'] = $username;
        }

        if (isset(static::$_responses['current_user'])) {
            static::$_responses['email'] = static::$_responses['current_user']['email'];
            static::$_responses['username'] = static::$_responses['current_user']['username'];
        }

        // resend activation code
        if (static::getRequest('resend')) {
            // resend welcome and activation mail.
            $model = static::getModel('user');

            $userData = [];
            if (isset(static::$_responses['current_user'])) {
                $userData = static::$_responses['current_user'];
            } else if ($email = static::getRequest('email')) {
                $userData = ['email' => $email];
            }

            if (!count($userData) || !$model->sendActivationMail($userData)) {
                static::$_responses['success'] = false;
                static::$_responses['message'] = '激活失败，请重试';
            } else {
                static::$_responses['success'] = true;
                static::$_responses['message'] = '激活邮件发送成功';
            }

          // process active user
        } elseif ($code = static::getRequest('code')) {
            $actived = false;

            $code = base64_decode($code);

            parse_str($code, $data);

            if (!$data || !count($data)) {
                static::$_responses['invalid_code'] = true;
                static::$_responses['message'] = '您的激活码无效，请重试!';
            }

            $model = static::getModel('user');
            if (!$model->updateActiveUser($data)) {

                if (isset($data['email'])) {
                    static::$_responses['email'] = $data['email'];
                }

                static::$_responses['actived'] = $actived;
                static::$_responses['message'] = 'Activation failed, please try again!';
            } else {
                $actived = true;
                static::$_responses['actived'] = $actived;
                static::$_responses['message'] = 'Congratulations, the plan is successful!';
            }
        }

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses['success'] = true;
            static::$_responses['content'] = static::loadLayout($isAjax);
            static::sendJson(static::$_responses);
        } else {
            static::loadLayout();
        }
    }

    /**
     * user login page
     *
     * @return string content html
     */
    public static function loginAction()
    {
        static::$_pageClass = 'user-login';

        static::$_pageTitle = '用户登陆';

        static::$_templates = [
            'base/header.phtml',
            'user/login.phtml',
            'base/footer.phtml'
        ];

        // login request form
        if (($login = static::getRequest('login'))) {
            $model = static::getModel('user');
            $reslut = $model->authentifiant($login);
            static::$_responses = array_merge(static::$_responses, $reslut);
        }

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses['content'] = static::loadLayout($isAjax);
            static::sendJson(static::$_responses);
        } else {

            if (isset(static::$_responses['current_user'])) {
                static::$_responses['success'] = true;
                static::$_responses['redirect_url'] = '/';
                static::redirectUrl('/');
            } else {

                if (isset(static::$_responses['redirect_url'])) {
                    static::redirectUrl(static::$_responses);
                } else {
                    static::loadLayout();
                }
            }
        }
    }

    /**
     * user show info page
     *
     * @return string content html
     */
    public static function showAction()
    {
        static::$_pageClass = 'user';

        static::$_pageTitle = 'Utilisateur';

        static::$_templates = [
            'base/header.phtml',
            'user/show.phtml',
            'base/footer.phtml'
        ];

        $model = static::getModel('user');
        $followerTotal = null;
        $videos = null;
        if ($user = $model->find(static::getRequest('id'))){
            $followerTotal = $model->followerTotal(static::getRequest('id'));
            $videos = $model->getVideos(static::getRequest('id'));
        }else {
            $user = null;
        }

        static::$_responses['followerTotal'] =  $followerTotal;
        static::$_responses['videos'] =  $videos;
        static::$_responses['user'] =  $user;

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses['success'] = true;
            static::$_responses['content'] = static::loadLayout($isAjax);
            static::sendJson(static::$_responses);
        } else {
            static::loadLayout();
        }
    }

    /**
     * user forget password page
     *
     * @return string content html
     */
    public static function forgetpasswordAction()
    {
        static::$_pageClass = 'user-forgetpassword';

        static::$_pageTitle = '找回密码';

        static::$_templates = [
            'base/header.phtml',
            'user/forgetpassword.phtml',
            'base/footer.phtml'
        ];


        // set new password for user
        if ($data = static::getRequest('forget')) {

            $model = static::getModel('user');

            static::$_responses['message'] = 'Change password. Please try again!';

            $result = $model->updateUserPassword($data);
            static::$_responses = array_merge(static::$_responses, $result);

            // verify forget password link
        } elseif ($code = static::getRequest('code')) {

            $code = base64_decode($code);

            parse_str($code, $data);

            static::$_responses['message'] = '您的激活码失效，请重试!';

            if (!$data || !count($data)) {
                static::$_responses['message'] = '您的激活码失效，请重试!';
            } else {

                $model = static::getModel('user');

                $user = $model->getUserByName($data['email']);

                if ($user) {

                    if ($user{0}->password == $data['key']) {
                        static::$_responses['success'] = true;
                        static::$_responses['message'] = '请输入您的新密码.';
                        static::$_responses['reset_password'] = true;
                        static::$_responses['user_id'] = $user{0}->id;
                    }
                }
            }

            // send forget password email to user
        } elseif ($email = static::getRequest('email')) {

            $model = static::getModel('user');
            $reslut = $model->forgetpassword($email);

            static::$_responses = array_merge(static::$_responses, $reslut);
        }

        if($isAjax = static::getRequest('isAjax')) {
            static::$_responses['content'] = static::loadLayout($isAjax);
            static::sendJson(static::$_responses);
        } else {
            static::loadLayout();
        }
    }

    /**
     * user profil page action
     *
     * @throws \Gam\Exception
     */
    public static function profilAction()
    {
        static::$_pageClass = 'user-profil';

        static::$_pageTitle = '个人中心';

        static::$_templates = [
            'base/header.phtml',
            'user/index.phtml',
            'base/footer.phtml'
        ];

        static::$_responses['success'] = true;

        //load user base info.
        $model = $model = static::getModel('user');

        if (isset(static::$_responses['current_user']) && static::$_responses['current_user']['id']) {
            $user = $model->getOne(static::$_responses['current_user']['id']);
            if (!$user) {
                unset(static::$_responses['current_user']);
            } else {
                static::$_responses['current_user'] = [
                    'id' => $user{0}->id,
                    'is_active' => '1',
                    'type' => $user{0}->type,
                    'avatar' => $user{0}->avatar,
                    'email' => $user{0}->email,
                    'username' => $user{0}->username,
                ];
            }
        }

        //update user profil
        if ($data = static::getRequest('profil')) {

            static::$_responses['success'] = false;
            static::$_responses['message'] = '操作异常，请重试';

            if ($data['id']) {
                //delete account
                if ('delete' == $data['button']) {
                    $result = $model->deleteUser($data);

                    static::$_responses = array_merge(static::$_responses, $result);

                    if ($result['success']) {
                        static::redirectUrl('/');
                    }

                } else if ('update' == $data['button']) {
                    $result = $model->updateUser($data);

                    if (isset($result['update_data'])) {
                        foreach($result['update_data'] as $key => $val) {

                            if (('birthday' == $key)) {
                                $val =  Date::formatDate($val);
                            }

                            static::$_responses['current_user'][$key] = $val;
                        }
                        unset($result['update_data']);
                    }

                    static::$_responses = array_merge(static::$_responses, $result);
                }
            }
        }

        if($isAjax = static::isAjax()) {

            if (!isset(static::$_responses['current_user'])) {
                static::$_templates = [
                    'user/login.phtml',
                ];
            } elseif(!static::$_responses['current_user']['is_active']){
                static::$_templates = [
                    'user/activation.phtml',
                ];
            }

            static::$_responses['success'] = true;
            static::$_responses['content'] = static::loadLayout($isAjax);
            static::sendJson(static::$_responses);
        } else {

            if (!static::$_responses['current_user']) {
                static::redirectUrl('user/login');

            } elseif(!static::$_responses['current_user']['is_active']){
                static::$_responses['redirect_url'] = 'user/active';
                static::redirectUrl(static::$_responses);
            }

            static::loadLayout();

        }
    }


}