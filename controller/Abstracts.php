<?php
/**
 * Projet Goask MeGam
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */

namespace Gam\Controller;

use \Gam\Helper\Exception;

abstract class Abstracts
{
    use \Gam\Config;

    /**
     * @var array default styles
     */
    protected static $_styles = [];

    /**
     * @var array default javascript
     */
    protected static $_javaScripts = [];

    /**
     * @var array reponse messages
     */
    protected static $_responses = [];

    /**
     * @var array current request data
     */
    protected static $_requests = [];

    /**
     * @var array current setting data
     */
    protected static $_settings = [];

    /**
     * @var array current template files
     */
    protected static $_templates = [];

    /**
     * @var array current page main class
     */
    protected static $_pageClass = '';

    /**
     * @var array current page title
     */
    protected static $_pageTitle = '';

    /**
     * @return string current page title
     */
    public static function getPageTitle()
    {
        return static::$_pageTitle;
    }

    /**
     * Start Sessions
     * 
     * @return null
     */
    public static function startSession()
    {
        session_start();

        static::$_responses['success'] = false;
        static::$_responses['message'] = '';

        // merge sessions paramtes
        if (isset($_SESSION['redirect_paramets']) && count($_SESSION['redirect_paramets'])) {
            foreach($_SESSION['redirect_paramets'] as $key => $val) {
                static::$_responses[$key] = $val;
            }
            unset($_SESSION['redirect_paramets']);
        }

        if (isset($_SESSION['current_user']) && count($_SESSION['current_user']) && isset($_SESSION['current_user']['id'])) {

            if (!$_SESSION['current_user']['is_active']) {
                unset($_SESSION['current_user']);
                unset(static::$_responses['current_user']);
            } else {

                // overload user info by database.
                $model = $model = static::getModel('user');
                $user = $model->getOne($_SESSION['current_user']['id']);

                // Forced to log out users who do not exist
                if (!$user) {
                    unset($_SESSION['current_user']);
                    unset(static::$_responses['current_user']);
                } else {

                    $_SESSION['current_user'] = [
                        'id' => $user{0}->id,
                        'is_active' => '1',
                        'byid' => $user{0}->byid,
                        'type' => $user{0}->type,
                        'avatar' => $user{0}->avatar,
                        'email' => $user{0}->email,
                        'password' => $user{0}->password,
                        'username' => $user{0}->username,
                    ];

                    static::$_responses['current_user']  = $_SESSION['current_user'];
                }
            }
        }
    }

    /**
     * Get cur page custom class name
     * @return string current page class
     */
    protected static function getPageClass()
    {
        return static::$_pageClass;
    }

    /**
     * Load page styles
     * @return array current page style
     */
    protected static function getPageStyles()
    {
        return static::$_styles;
    }

    /**
     * Load page styles
     *
     */
    protected static function getPageJavascripts()
    {
        return static::$_javaScripts;
    }

    /**
     * Getter url domain
     * @return string
     */
    public static function getUrl($path='')
    {
        $url = static::getRequest('url');

        if (strlen($path) > 1) {
            $url .= $path;
        }
        return $url;
    }

    /**
     * Rediction page
     *
     * TODO: Manage form errors between two controllers
     */
    public static function redirectUrl($paramets)
    {
        if ((is_array($paramets) && !count($paramets) && !isset($paramets['redirect_url']))) {
            throw new Exception("Warning: url is required");
        } else if (!$paramets) {
            throw new Exception("Warning: url is required");
        }

        if (is_array($paramets)) {
            $redirect_url = $paramets['redirect_url'];
            unset($paramets['redirect_url']);
            $_SESSION['redirect_paramets'] = $paramets;
        } else {
            $redirect_url = $paramets;
        }

        if (isset($redirect_url)) {
            $redirect_url = static::getUrl($redirect_url);
            header("Location: {$redirect_url}");
        }
    }

    /**
     * send json content to body
     *
     * @return json|null
     */
    protected static function sendJson($responses = [])
    {
        if(!count($responses)) {
            return;
        }

        $responses = array_merge($responses, [
            'class' => static::getPageClass(),
            'title' => static::getPageTitle(),
            'styles' => static::getPageStyles(),
            'javascripts' => static::getPageJavascripts(),
        ]);

        print_r(json_encode($responses));
        exit();
    }

    /**
     * is ajax mode
     *
     * @return boolean
     */
    protected static function isAjax()
    {
        $isAjax = false;
        if ('XMLHttpRequest' == static::getRequest('HTTP_X_REQUESTED_WITH') || static::getRequest('isAjax')) {
            $isAjax = true;
        }

        return $isAjax;
    }

    /**
     * Getter val request
     * @return string
     */
    public static function getRequest($name='', $asArray = false)
    {
        return static::$_requests[$name] ?? (static::$_requests['request_parameters'][$name] ?? ($asArray ? self::$_requests['request_parameters'] : false));
    }

    /**
     * Getter data
     * @return string||array
     */
    public static function getSettings($name='', $asArray = false)
    {
        return static::$_settings[$name] ?? ($asArray ? static::$_settings : false);
    }

    /**
     * Getter an model
     */
    public static function getModel($name)
    {
        $className = '\\Gam\\Model\\'. ucfirst($name);

        if (!class_exists($className)) {
            throw new Exception("Warning: {$className} is not class");
        }

        return new $className;
    }

    /**
     * Initialize les configs
     */
    public static function dispatche()
    {
        // define scheme
        $scheme = ((isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        self::$_requests = array_merge(['request_parameters'=>[]], $_SERVER, ['url' => $scheme . $_SERVER['HTTP_HOST'] .'/', 'is_admin' => false]);

        // set all request parametes
        // filter url controler and action name

        if (count($_REQUEST)) {
            foreach($_REQUEST as $_key => $_val) {
                if (!$_val) {
                    continue;
                }

                self::$_requests['request_parameters'][$_key] = $_val;
            }
        }

        // find controller/action by uri path
        $pattern = '/^\/(\w+)?\/?(\w+)?([^\?]+)?/i';
        preg_match($pattern, self::$_requests['REQUEST_URI'], $_urlPaths);

        $_urlPaths = array_filter($_urlPaths);

        if (count($_urlPaths)) {

            foreach($_urlPaths as $_key => $_val) {
                // filter first full path
                if (!$_key) {
                    continue;
                }

                // filter empty value
                if (!$_val) {
                    unset($_urlPaths[$_key]);
                    continue;
                }

                // set controller
                if (1 == $_key) {
                    self::$_requests['controller'] = '\\' . __NAMESPACE__ . '\\' . ucfirst($_val);
                    unset($_urlPaths[$_key]);
                }

                // set action
                if (2 == $_key) {
                    // exclude specialties name for action
                    if (in_array($_val, ['id'])) {
                        $next = $_key + 1;
                        if (!isset($_urlPaths[$next])) continue;

                        self::$_requests['id'] = $_urlPaths[$next];

                    } elseif (is_numeric($_val)) {
                        self::$_requests['id'] = $_val;
                    } else {
                        self::$_requests['action'] = $_val . 'Action';
                    }
                    unset($_urlPaths[$_key]);
                }

                // set url arguement
                if ($_key >= 3) {

                    // filter '/'
                    $_val = substr($_val, 1);

                    if (is_numeric($_val)) {
                        if(!isset($_urlPaths[$_key + 1])) {
                            self::$_requests['id'] = $_val;
                        }
                    } else {

                        $_parameters = explode('/', $_val);

                        if (!count($_parameters)) break;

                        $_parameters = array_values($_parameters);

                        //set request argument by url parameter
                        foreach ($_parameters as $_nkey => $_nval) {

                            $next = $_nkey + 1;
                            if (!$_nval) {
                                continue;
                            }

                            if (!($_nkey % 2) && isset($_parameters[$next])) {
                                self::$_requests[$_nval] = $_parameters[$next];
                                self::$_requests['request_parameters'][$_nval] = $_parameters[$next];
                            }
                        }
                        break;

                    }
                }
            }
        }

        // set default controller and action
        if (!isset(self::$_requests['controller'])) {
            self::$_requests['controller'] = '\\' . __NAMESPACE__ . '\\' . 'Index';
        }

        if (!isset(self::$_requests['action'])) {
            self::$_requests['action'] = 'indexAction';
        }

        // check class controller before access.
        if (!class_exists(self::$_requests['controller'], $autoload = true)) {
            header('Location: /error');
            exit;
        }

        // start session
        call_user_func_array(array(self::$_requests['controller'], 'startSession'), []);

        // call action by cur controller
        if (isset(self::$_requests['action']) && method_exists(self::$_requests['controller'], self::$_requests['action'])) {
            call_user_func_array(array(self::$_requests['controller'], self::$_requests['action']), []);
        }

        // load template
        //call_user_func_array(array(self::$_requests['controller'], 'loadLayout'), []);

    }

    /**
     * Load template file dynamise
     *
     * @return string|null
     */
    public static function loadTemplate($templateFile = '')
    {
        $_templatePath = TEMPLATE_PATH . DS . (isset(self::$_requests['is_admin']) && self::$_requests['is_admin'] ? 'adminhtml' : 'frontend' ). DS;

        if (!file_exists($_templatePath . $templateFile)) {
          throw new Exception("Warning: {$templateFile} Page Template not found.");
        }

        include_once $_templatePath . $templateFile;
    }

    /**
     * Load template file
     *
     * @return string|null
     */
    public static function loadLayout($isAjax=false)
    {
        if (!isset(static::$_templates) || !count(static::$_templates)) {
            throw new Exception("Warning: ". static::class . " Page not define template.");
        }

        $output = $isAjax;

        // ajax catch content
        if($output) {
            ob_start();
        }

        foreach(static::$_templates as $_template) {

            // filter header and footer in ajax request
            if ($output && preg_match('/header|footer/i', $_template)) {
                continue;
            }

            static::loadTemplate($_template);
        }

        // ajax output content
        if($output) {
            return ob_get_clean();
        }

    }

    /**
     * Get the global path to video directory
     * 
     * @return string
     */
    public static function getVideoPath($path = ''){
        return 'videos/'. $path;
    }

    /**
     * Retriving a file
     *
     */
    public static function uploadFile($file, $dir){
        if (file_exists($dir . $file["name"])){
            // Le fichier est déjà en local
        }
        else{
            // Stockage du fichier en local
            move_uploaded_file($file["tmp_name"],
                               $dir . $file["name"]);
        }
    }
}