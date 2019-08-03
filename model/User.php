<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */
namespace Gam\Model;

use \Gam\Helper\Core;
use \Gam\Helper\Mail;
use \Gam\Helper\Bytom;

class User extends Abstracts {

    // password hast const

    const PASSWORD_CONST = 8;

    /**
     * delete user account by user id
     *
     * @param $id
     * @return boolean
     */
    public function deleteUser($data= [])
    {
        $result = ['success'=> false, 'message' => '操作失败，请重试!'];

        if (!count($data) || !isset($data['id'])) {
            return $result;
        }

        $id = $data['id'];
        $user = $this->getOne($id);

        if (!$user) {
            return $result;
        }

        if (!isset($data['old_password'])) {
            $result['message'] = '在删除您账户前，您必须输入当前密码.';
            return $result;
        }

        if(!password_verify($data['old_password'], $user{0}->password)) {
            $result['message'] = '您的密码不正确';
            return $result;
        }

        $_data = [
            'table' => 'customer',
            'operation' => 'delete',
            'where' => 'id=:id',
            'parametes' => [':id' => $id ]
        ];

        if ($this->deleteData($_data)) {
            $result['success'] = true;
            $result['message'] = '您的账户已被删除';

            Core::logoutCurrentUser();
        }
        return $result;
    }

    /**
     * Send usr activation mail
     * @return boolean
     */
    public function forgetpassword($email='')
    {
        $result = ['success'=> false, 'message' => '发送失败，您重试!'];

        if (!$email) {
            $result['message'] = '请输入您的邮箱地址';
            return $result;
        }

        $user = $this->getUserByName($email);
        if (!$user) {
            $result['message'] = '该用户不存在';
            return $result;
        }

        $key = Core::getActivationKey();

        $_data = [
            'table' => 'customer',
            'operation' => 'update',
            'fields' => [
                'password' => $key
            ],
            'where' => 'id=:id',
            'parametes' => [':id' => $user{0}->id ]
        ];

        $activation_key = Core::getUrl('user/forgetpassword/code/'.base64_encode('email=' . $email.'&key=' . $key));

        if (Mail::sendForgetPassword([
                'email' => $email,
                'activate_link'=> $activation_key
            ]) && $this->updateData($_data)) {
            $result['success'] = true;
            $result['message'] = '我们发送了一封找回密码的邮件到您的邮箱，您注意查收.: <span class="text-color-s1">' . $email . '</span>';
            return $result;
        }

        return $result;

    }

    /**
     * update user password
     *
     * @param $data
     * @return boolean|string
     */
    public function updateUserPassword($data=[])
    {
        $result = ['success'=> false, 'message' => '操作异常，请重试!'];

        if (!count($data) || !isset($data['id'])) {
            return $result;
        }

        $id = $data['id'];
        $user = $this->getOne($id);

        if (!$user) {
            return $result;
        }

        $newpassword = password_hash($data['password'], PASSWORD_DEFAULT, ["cost" => self::PASSWORD_CONST]);

        $_data = [
            'table' => 'customer',
            'operation' => 'update',
            'fields' => ['password' => $newpassword],
            'where' => 'id=:id',
            'parametes' => [':id' => $id ]
        ];

        if ($this->updateData($_data)) {
            $result['success'] = true;
            $result['message'] = '您的新密码已生效，请点击<a class="button-s2 ml-1" href="/user/login/">登陆</a>';
        }

        return $result;
    }


    /**
     * update user data
     *
     * @param $data
     * @return boolean|string
     */
    public function updateUser($data=[])
    {
        $result = ['success'=> false, 'message' => '操作异常请重试!'];

        if (!count($data) || !isset($data['id'])) {
            return $result;
        }

        $id = $data['id'];
        $user = $this->getOne($id);

        if (!$user) {
            return $result;
        }

        if (isset($_FILES['profil'])) {

            $tmp_avatar_path = $_FILES['profil']['tmp_name']['avatar'];

            if (file_exists($tmp_avatar_path)) {
                $tmp_avatar_info = pathinfo($_FILES['profil']['name']['avatar']);

                if (!in_array($tmp_avatar_info['extension'], ['jpg','png','git'])) {
                    $result['message'] = '请选择正确的文件格式:jpg，png，git.';
                    return $result;
                }

                $filename = md5($tmp_avatar_info['filename'] . $id) . '.' .$tmp_avatar_info['extension'];

                $newfile = ROOT_PATH . '/views/images/avatars/' . $filename;

                if(!move_uploaded_file($tmp_avatar_path, $newfile)) {
                    $result['message'] = '请重新上传一次';
                    return $result;
                } else {
                    $data['avatar'] = $filename;
                }
            }
        }

        if (isset($data['new_password']) && !isset($data['old_password'])) {
            $result['message'] = '您当前密码不对';
            return $result;
        }

        $_oldPassword = $data['old_password'];
        $_allowFileds = ['new_password', 'avatar'];

        foreach($data as $key => $val) {

            if (!in_array($key, $_allowFileds) || empty($val)) {
                unset($data[$key]);
                continue;
            }

            if (('birthday' == $key)) {

                if (!($birthdayFormat = \DateTime::createFromFormat('d/m/Y', $val))) {
                    $result['message'] = '请输入正确的日期格式';
                    return $result;
                }

                $data[$key] = $birthdayFormat->format('Y-m-d');;
            }

            if ('new_password' == $key) {

                if (empty($val)) {
                    unset($data[$key]);
                    continue;
                }

                if(!password_verify($data['old_password'], $user{0}->password)) {
                    $result['message'] = '您当前密码不对';
                    return $result;
                }

                $data[$key] = password_hash($data[$key], PASSWORD_DEFAULT, ["cost" => self::PASSWORD_CONST]);
            }
        }

        $_data = [
            'table' => 'customer',
            'operation' => 'update',
            'fields' => $data,
            'where' => 'id=:id',
            'parametes' => [':id' => $id ]
        ];

        if ($this->updateData($_data)) {
            $result['success'] = true;
            $result['message'] = '资料更新成功!';
            $result['update_data'] = $data;
        }

        return $result;
    }

    /**
     * get user data by username or email
     * @param array $paramete
     * @return array
     */
    public function getUserByName($email='', $options = [])
    {
        $user = [];

        if (!$email) {
            return $user;
        }

        $_querys = [
            'table' => 'customer',
            'fields' => ['id', 'username','byid','email', 'password', 'is_active', 'avatar', 'type'],
            'where' => 'username=:username',
            'parametes' => [
                ':username' => $email
            ]
        ];

        if (isset($options['fields'])) {
            $_querys['fields'] =  array_merge($_querys['fields'], $options['fields']);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_querys['where'] = 'email=:email';
            $_querys['parametes'] = [
                ':email' => $email
            ];
        }

        if (!($user = $this->getData($_querys))) {
            return $user;
        }

        return $user;
    }

    /**
     * update user activation status
     * @return boolean
     */
    public function updateActiveUser($parameter=[])
    {
        if (!count($parameter) || !isset($parameter['email'])) {
            return false;
        }

        $identifant = $parameter['email'];

        if (!isset($parameter['email'])) {
            if (isset($parameter['username'])) {
                $identifant = $parameter['username'];
            } else {
                return false;
            }
        }

        if (!$identifant) {
            return false;
        }

        $user = $this->getUserByName($identifant, ['fields' => ['confirmation']]);

        if (!$user) {
            return false;
        }

        if ($user{0}->is_active) {
            return true;
        }

        if ($parameter['key'] === $user{0}->confirmation) {

            $_data = [
                'table' => 'customer',
                'operation' => 'update',
                'fields' => [
                    'is_active' => '1',
                    'confirmation' => ''
                ],
                'where' => 'id=:id',
                'parametes' => [':id' => $user{0}->id ]
            ];

            return $this->updateData($_data);
        }

        return false;
    }

    /**
     * Send usr activation mail
     * @return boolean
     */
    public function sendActivationMail($parameter=[])
    {
        $success = true;

        if (!isset($parameter['email'])) {
            return false;
        }

        if (isset($parameter['is_active']) && $parameter['is_active']) {
            return true;
        }

        if (!isset($parameter['activation_key'])) {

            $user = $this->getUserByName($parameter['email']);

            if ($user) {

                $parameter['activation_key'] = Core::getActivationKey();

                $_data = [
                    'table' => 'customer',
                    'operation' => 'update',
                    'fields' => [
                        'confirmation' => $parameter['activation_key']
                    ],
                    'where' => 'id=:id',
                    'parametes' => [':id' => $user{0}->id ]
                ];

                if (!isset($parameter['username'])) {
                    $parameter['username'] =  $user{0}->username;
                }

                if (!$this->updateData($_data)) {
                    $success = false;
                }
            }
        }

        $parameter['activation_key'] = Core::getUrl('user/active/code/'.base64_encode('username='.$parameter['username'].'&email=' . $parameter['email'].'&key=' . $parameter['activation_key']));

        if ($success && !Mail::sendActivation([
            'username' => $parameter['username'],
            'email' => $parameter['email'],
            'activate_link'=> $parameter['activation_key']
        ])) {
            $success = false;
        }

        return $success;

    }

    /**
     * get all user by condition
     * @param array
     * @return array
     */
	public function getAll($optinos=[]){
		$_querys = [
			'table' => 'customer',
            'fields' => ['*'],
            'order' => 'id ASC'
        ];

		if (isset($optinos['where'])) {
            $_querys['where'] = $optinos['where'];
        }

        return $this->getData($_querys);
	}

    /**
     * Get one user using his ID
     *
     * @param $userId
     * @return array
     */
    public function getOne($userId)
    {
        $_query = [
            'table' => 'customer',
            'fields' => ['DISTINCT *'],
            'where' => 'id = :id',
            'parametes' => [':id' => $userId],
        ];

        return $this->getData($_query);
    }

    /**
     * Find a user by his Id
     *
     * @param array $data
     * @return null|\Exception $e
     */
    public function find($userId)
    {
        $_querys = [
            'table' => 'customer',
            'fields' => ['*'],
            'where' => 'id = :id',
            'parametes' => [':id' => $userId]
        ];
        return $this->getData($_querys);
    }

    /**
     * Register User Account
     *
     * @param array $data
     * @return null|\Exception $e
     */
    public function register($data=[])
    {
        $response = ['success'=> false, 'message'=>'注册失败，请稍后尝试.'];

        if (empty($data['email']) || empty($data['password'])) {
            $response = [
                'success'=> false,
                'fields' => 'email|password',
                'message'=> '密码或邮箱不能为空!'
            ];
            return $response;
        }

        if ($data['password'] != $data['password_confirmation']) {
            $response = [
                'success'=> false,
                'fields' => 'password',
                'message'=> '请核对您的密码.'
            ];
            return $response;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $response = [
                'success'=> false,
                'fields' => 'email',
                'message'=> '请输入有效的邮箱.'
            ];
            return $response;
        }

        $_querys = [
            'table' => 'customer',
            'fields' => ['id'],
            'where' => 'email=:email OR username=:username',
            'parametes' => [
                ':email' => $data['email'],
                ':username' => $data['username']
            ]
        ];

        if ($this->getData($_querys)) {
            $response = [
                'success'=> false,
                'fields' => 'email',
                'message'=>  '该邮箱已注册，请您<a class="button-s2" href="user/login/">登陆</a>'
            ];
            return $response;
        }

        $passwrod =  password_hash($data['password'], PASSWORD_DEFAULT, ["cost" => self::PASSWORD_CONST]);

        $activationKey = Core::getActivationKey();

        //Synchronise Via Bytom
        $bytomAccountId = Bytom::createAccount($data['email'], $data['password']);

        if (!$bytomAccountId) {
            return $response;
        }

        $_querys = [
            'table' => 'customer',
            'operation' => 'insert',
            'fields' => [
                'byid'  => $bytomAccountId,
                'email' => $data['email'],
                'username' => $data['username'],
                'confirmation' => $activationKey,
                'password' => $passwrod,
                'type' => $data['type']
            ]
        ];

        if ($this->insertData($_querys)) {

            // send welcome and activation mail.
            $sendMail =  $this->sendActivationMail([
                'username' => $data['username'],
                'email' => $data['email'],
                'activation_key'=> $activationKey
            ]);

            $response = [
                'success'=> $sendMail,
                'redirect_url'=>'user/active',
                'username' => $data['username'],
                'email' => $data['email'] ,
                'message'=> '欢迎您的到来。'
            ];
        }
        return $response;
    }

    /**
     * Au User Account
     *
     * @param array $data
     * @return null|\Exception $e
     */
    public function authentifiant($data=[])
    {
        $response = ['success'=> false, 'username'=> $data['username'], 'message'=>'登陆失败，请重试'];

        if (!isset($data['username']) || !isset($data['password'])) {
            $response['fields'] = 'username';
            $response['message'] = '请输入您的用户名或密码';
            return $response;
        }

        $user = $this->getUserByName($data['username']);

        if (!$user) {
            $response['fields'] = 'username|password';
            $response['message'] = '请检查您的用户名或密码';
            return $response;
        }

        // check email activation
        if (!$user{0}->is_active) {
            $response['message'] = '您点击链接<a class="button-s2" href="user/active/resend/true/email/' .$user{0}->email.'">激活</a>您的账户.';
            return $response;
        }

        if(!password_verify($data['password'], $user{0}->password)){
            $response['fields'] = 'password';
            $response['message'] = '密码错误，请重试';
            return $response;
        }

        $_SESSION['current_user'] = [
            'id' => $user{0}->id,
            'is_active' => $user{0}->is_active,
            'type' => $user{0}->type,
            'byid' => $user{0}->byid,
            'avatar' => $user{0}->avatar,
            'email' => $user{0}->email,
            'username' => $user{0}->username,
        ];

        $response = [
            'success'=> true,
            'redirect_url'=>'profil',
            'email' => $user{0}->email,
            'username' => $user{0}->username,
            'current_user' => $_SESSION['current_user'],
            'message'=>'登陆成功，欢迎您!',
        ];

        return $response;
    }

}