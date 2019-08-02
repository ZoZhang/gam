<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */
namespace Gam\Model;

use \Gam\Helper\Core;
use \Gam\Helper\Bytom;

class Contract extends Abstracts {

    protected $status = [
        'CREATED' => '创建',
        'PENDING' => '待完成',
        'FINISHED' => '完成',
    ];

    /**
     * Create Contract
     *
     * @param array $data
     * @return void
     */
    public function create($data=[])
    {
        $response = ['success'=> false, 'message'=>'创建失败，请稍后尝试.'];

        if (empty($data['title']) || empty($data['content']) || empty($data['reward'])) {
            $response = [
                'fields' => 'title|content',
                'message'=> '标题内容或奖励不能为空!'
            ];
            return $response;
        }

        $currentUser = Core::getCurrentUser();

        if ($currentUser['type'] != 'enterprise'){
            $response = [
                'message'=> '个人用户不能发布任务!'
            ];
            return $response;
        }

        $contract = $this->getContract(['title'=> $data['title'], 'customer_id' => $currentUser['id']]);

        if ($contract) {
            $response = [
                'message'=> '您已发布此任务!'
            ];
            return $response;
        }

        // call bytom api
//        $account = Bytom::getAccount();

//        $account = [];
//
//        if (!$account) {
//            $response = [
//                'message'=> '您当前账户资金不足此发布此任务!'
//            ];
//            return $response;
//        }

        $_querys = [
            'table' => 'contract',
            'operation' => 'insert',
            'fields' => [
                'customer_id' => $currentUser['id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'reward' => $data['reward'],
                'status' => 'CREATED',
                'push' => 0,
                'program' => '',
                'locked'  => 1
            ]
        ];

        if ($id = $this->insertData($_querys)) {
            $response = [
                'success'=> true,
                'redirect_url'=>'contract/view/' . $id,
                'message'=> '恭喜您任务发布成功。'
            ];
        }
        return $response;
    }

    /**
     * get contract
     * @param array $paramete
     * @return array|void
     */
    public function getContract($paramete = [], $fields = [])
    {
        $data = [];
        $where = '';
        $parametes = [];

        if (count($paramete)) {
            foreach($paramete as $key => $val) {

                if ($where) {
                    $where .= ' AND ';
                }

                $where .= sprintf(' %s=:%s', $key, $key);
                $parametes[':' . $key] = $val;
            }
        } else {
            $where = ' 1 ';
        }

        $_querys = [
            'table' => 'contract',
            'fields' => !count($fields) ? '*' : $fields,
            'where' => $where,
            'parametes' => $parametes,
            'order' => 'created_at desc',
        ];

        $data = $this->getData($_querys);

        foreach ($data as &$item) {
            $item->status = $this->status[$item->status];;

        }

        return $data;
    }


}