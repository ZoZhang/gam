<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */

namespace Gam\Helper;

use \vendor\SendGrid\Api as SendGridApi;
use \vendor\SendGrid\Main as SendGridClient;

class Mail {

    use \Gam\Config;

    // user email templtes
    const EMAIL_TEMPLATE_WELCOME = 'user/welcome.phtml';
    const EMAIL_TEMPLATE_FORGET_PASSWORD = 'user/forgetpassword.phtml';
    const EMAIL_TEMPLATE_NEWS_VIDEOS = 'user/lastvideos.phtml';

    /**
     * get email html template content
     * @param $template path file
     * @param $paramets array
     * @return string
     */
    public static function getTemplateContent($template='', $paramets = [])
    {
        $templateFile = TEMPLATE_PATH . DS . 'frontend' . DS . 'emails' . DS . $template;

        if (!file_exists($templateFile) || !is_readable($templateFile)) {
            return false;
        }

        $templateContent = file_get_contents($templateFile);

        if (!$templateContent) {
            return false;
        }

        if (count($paramets)) {

            $variables = [];
            array_walk($paramets, function ($value, $key) use (&$variables) {
                $variables['{{' . $key . '}}'] = $value;
            });

            $templateContent = str_replace(array_keys($variables), $variables, $templateContent);
        }

        return $templateContent;
    }

    /**
     * Send Mail by SendGrid API
     * @param $subjet string
     * @param $sendTo string email addresse
     * @param $username string
     * @param $templateFile string
     * @param $paramets array
     */
    public static function send($sujet='', $sendTo='', $username='', $templateFile = '', $paramets = [])
    {
        $send = false;
        if (!$sujet || !$sendTo || !$username || !$templateFile) {
            return false;
        }

        $templateContent = self::getTemplateContent($templateFile, $paramets);

        if (!$templateContent) {
            return false;
        }

        $email = new SendGridApi();
        $email->setSubject($sujet);
        $email->setFrom(
            static::getConfig('store','email'),
            static::getConfig('store','contact')
        );

        $email->addTo($sendTo, $username);
        $email->addContent("text/html", $templateContent);

        $sendgrid = new SendGridClient(static::getConfig('sendGrid','clientKey'));
        try {
            $response = $sendgrid->send($email);

            if (in_array($response->statusCode(), ['200', '202'])) {
                $send = true;
            }

        } catch (Exception $e) {
            //throw new \Exception('Email exception: '.  $e->getMessage(). "\n");
        }
        return $send;
    }

    /**
     * Send user activation mail
     * @param $paramets array
     */
    public static function sendActivation($parameter=[])
    {
        if (!count($parameter) || !isset($parameter['email']) || !isset($parameter['activate_link'])) {
            return false;
        }

        return self::send("欢迎来到问我呗！", $parameter['email'], $parameter['username'],self::EMAIL_TEMPLATE_WELCOME, $parameter);
    }

    /**
     * Send user forget password mail
     * @param $paramets array
     */
    public static function sendForgetPassword($parameter=[])
    {
        if (!count($parameter) || !isset($parameter['email']) || !isset($parameter['activate_link'])) {
            return false;
        }

        return self::send("请修改您的密码", $parameter['email'],'customer',self::EMAIL_TEMPLATE_FORGET_PASSWORD, $parameter);
    }

}