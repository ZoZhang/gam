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
use \Gam\Helper\Exception;

class Contract extends Abstracts {

    /**
     * Create Contract
     *
     * @param array $data
     * @return void
     */
    public function create($data=[])
    {
        $response = ['success'=> false, 'message'=>'发布失败，请稍后尝试.'];

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

        //check account amount
//        if (!$account) {
//            $response = [
//                'message'=> '您当前账户资金不足此发布此任务!'
//            ];
//            return $response;
//        }

        // create contract to bytom
        $program = Bytom::createContract($data['title']);
        if (is_array($program) && !$program['success']) {
            $response = [
                'message'=> '发布失败，请检查bytom服务! ' . $program['message']
            ];
            return $response;
        }

        $txid = $pushContract = Bytom::pushContract([
            'byid'  => $currentUser['byid'],
            'program'  => $program,
            'password'  => $currentUser['password'],
        ]);

        if (isset($txid['success']) && !$txid['success']) {
            $response = [
                'message'=> '推送失败，请检查bytom服务! ' . $txid['message']
            ];
            return $response;
        }

        if (!$txid) {
            $response = [
                'message'=> '推送失败，请检查bytom服务!'
            ];
            return $response;
        }

        $_querys = [
            'table' => 'contract',
            'operation' => 'insert',
            'fields' => [
                'customer_id' => $currentUser['id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'reward' => $data['reward'],
                'status' => 'CREATED',
                'txid' => $txid,
                'push' => 0,
                'program' => $program,
                'locked'  => 0
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
     * Delegation Contract
     *
     * @param array $data
     * @return void
     */
    public function delegation($data=[])
    {
        $response = ['success'=> false, 'message'=>'领取失败，请稍后尝试.'];

        $contract = $this->getContract(['id'=> $data['id']]);

        if (!count($contract)) {
            $response = [
                'message'=> '该任务不存在，请核实后重试!',
                'redirect_url'=>'contract/list/'
            ];
            return $response;
        }

        $currentUser = Core::getCurrentUser();

        if (!isset($currentUser['id'])) {
            return $response;
        }

        if ($currentUser['type'] == 'enterprise'){
            $response = [
                'message'=> '非个人用户不能接受任务',
                'redirect_url'=>'contract/list/'
            ];
            return $response;
        }

        if ($contract{0}->status == 'FINISHED') {
            $response = [
                'message'=> '该任务已被委派!',
                'redirect_url'=>'contract/list/'
            ];
            return $response;
        }

        if ($contract{0}->locked || $contract{0}->delegation_id) {
            $response = [
                'message'=> '该任务已被委派!',
                'redirect_url'=>'contract/list/'
            ];
            return $response;
        }

        $_querys = [
            'table' => 'contract',
            'operation' => 'update',
            'fields' => [
                'locked'    => 1,
                'status' => 'PENDING',
                'delegation_id' => $currentUser['id']
            ],
            'where' => 'id=:id',
            'parametes' => [':id' => $contract{0}->id]
        ];

        if ($id = $this->updateData($_querys)) {
            $response = [
                'success'=> true,
                'redirect_url'=>'contract/view/' . $id,
                'message'=> '恭喜您领取成功。'
            ];
        }
        return $response;
    }

    /**
     * update bytom cid for contract
     * @return bool|void
     * @throws \Exception
     */
    public function pull()
    {
        $contracts = $this->getContract();

        if (!count($contracts)) {
            return false;
        }

        $txids = [];
        foreach($contracts as $contract) {

            if ($contract->cid) {
                continue;
            }

            $txids[] = $contract->txid;
        }

        $cids = Bytom::pullContract([
            'txid'  => $txids
        ]);

        if (isset($cids['success']) && !$cids['success']) {
            Exception::logger(print_r($cids, true), 1);
            return;
        }

        foreach($contracts as $contract) {

            if ($contract->cid) {
                continue;
            }

            if (!isset($cids[$contract->txid])) {
                continue;
            }

            $_querys = [
                'table' => 'contract',
                'operation' => 'update',
                'fields' => [
                    'push'    => 1,
                    'cid'    => $cids[$contract->txid]
                ],
                'where' => 'id=:id',
                'parametes' => [':id' => $contract->id]
            ];

            if ($id = $this->updateData($_querys)) {
               print_r(sprintf('Update Contract Cid Success: %s->%s' . PHP_EOL, $contract->id, $cids[$contract->txid]));
            }
        }
        return;
    }

    /**
     * Finish Contract
     *
     * @param array $data
     * @return void
     */
    public function finish($data=[])
    {
        $response = ['success'=> false, 'message'=>'确认失败，请稍后尝试.'];

        $contract = $this->getContract(['id'=> $data['id']]);

        if (!count($contract)) {
            $response = [
                'message'=> '该任务不存在，请核实后重试!',
                'redirect_url'=>'contract/list/'
            ];
            return $response;
        }

        $currentUser = Core::getCurrentUser();

        if ($currentUser['type'] != 'enterprise'){
            $response = [
                'message'=> '您没有权限完成任务',
                'redirect_url'=>'contract/list/'
            ];
            return $response;
        }

        if ($contract{0}->customer_id != $currentUser['id']) {
            $response = [
                'message'=> '只有发者人有权限完成任务!',
                'redirect_url'=>'contract/list/'
            ];
            return $response;
        }

        if (!$contract{0}->delegation_id) {
            $response = [
                'message'=> '该任务未被委派',
                'redirect_url'=>'contract/list/'
            ];
            return $response;
        }

        $_querys = [
            'table' => 'contract',
            'operation' => 'update',
            'fields' => [
                'locked'    => 0,
                'status' => 'FINISHED'
            ],
            'where' => 'id=:id',
            'parametes' => [':id' => $contract{0}->id]
        ];

        // freed contract to bytom
        $txid = Bytom::freedContract([
            'cid' => $contract{0}->cid,
            'byid' => $data['byid'],
            'password' => $data['password'],
            'unlockkey' => $contract{0}->program,
        ]);

        if (isset($txid['success']) && !$txid['success']) {
            $response = [
                'redirect_url'=>'contract/view/' .$contract{0}->cid,
                'message'=> '解锁合约失败，请检查bytom服务! ' . $txid['message']
            ];
            return $response;
        }

        if ($id = $this->updateData($_querys)) {
            $response = [
                'success'=> true,
                'redirect_url'=>'contract/view/' . $id,
                'message'=> '恭喜任务完成。'
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

        return $data;
    }
}
