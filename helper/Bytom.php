<?php
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */
namespace Gam\Helper;

use Bytom\BytomClient;
use Bytom\CurlHttpClient\CurlHttpClient;
use \Gam\Helper\Exception;

class Bytom {

    protected static $_bytomClient = null;

    CONST BYTOM_HOST_URI = 'http://74.82.218.9:9888';

    /**
     * get bytom client
     * @return BytomClient|null
     */
    public static function getBytomClient()
    {
        if (is_null(self::$_bytomClient)) {
            self::$_bytomClient = new BytomClient(self::BYTOM_HOST_URI);
        }

        return self::$_bytomClient;
    }

    /**
     * Create bytom account
     * @param string $username
     * @param string $password
     * @return array|integer
     */
    public static function createAccount($username = '', $password = '')
    {
        $response = ['success'=> false, 'message'=>'创建账户失败'];
        if (empty($username) || empty($password)) {
            return $response;
        }

        $res = self::getBytomClient()->listKeys();
        $data = $res->getJSONDecodedBody();

        if($data["status"] == "success"){
            $mockHsmKey = $data["data"][0];

            $tmpXpubs = array();
            $tmpXpubs[] = $mockHsmKey["xpub"];

            $res = self::getBytomClient()->createAccount($tmpXpubs,$username);
            $data = $res->getJSONDecodedBody();
            if($data["status"] == "success"){
                $byId = $data["data"]["id"];

                $res = self::getBytomClient()->createAccountReceiver($data["data"]["alias"],$byId);
                $data = $res->getJSONDecodedBody();
                if($data["status"] == "success"){
                    return $byId;
                }
            }
        } else {
            Exception::logger(print_r($data, true), 1);
        }

        $response['message'] = $data['msg'];

        return $response;
    }

    /**
     * create contract to bytom
     * @param string $title
     * @param string $contract
     * @return array|string
     */
    public static function createContract($title = '')
    {
        $response = ['success'=> false, 'message'=>'创建合约失败'];

        if (empty($title)) {
            return $response;
        }

        $hash = hash("sha3-256", 'gam' . $title);
        $xbhttpClient = new CurlHttpClient("");

        $args = array();
        $args[] = ['string' => $hash];

        $contract = 'contract SecretContract(hash: Hash) locks valueAmount of valueAsset { clause reveal(string: String) { verify sha3(string) == hash unlock valueAmount of valueAsset }}';

        $params = ['contract' => $contract, 'args' => $args];

        $result = $xbhttpClient->post(self::BYTOM_HOST_URI . '/compile', $params);

        $data = $result->getJSONDecodedBody();

        if($data["status"] == "success"){
            return $data["data"]["program"];
        }

        $response['message'] = $data['msg'];

        Exception::logger(print_r($data, true), 1);

        return $response;
    }

    /**
     * push contract to bytom
     * @param array $parametes
     * @return array|integer
     */
    public static function pushContract($parametes = [])
    {
        return 'e710d63a81f2cc1febcf989c547d2576e58b51961461885a378e514a197b39c0';
      $response = ['success'=> false, 'message'=>'推送合约失败'];

      if (!count($parametes)) {
            return $response;
      }

      $spendAction = [
        "account_id" => $parametes['byid'],
        "amount" => 1000000,
        "asset_id" => "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff",
        "type" => "spend_account"
      ];

      $vbAction = [
        "account_id" => $parametes['byid'],
        "amount" => 10000000000,
        "asset_id" => "31434830dd7af31d7bb2aed3942cbc15f5ad78c438c11ff52caef10a05bef40c",
        "type" => "spend_account"
      ];

      $controlAction = [
        "amount" => $vbAction["amount"],
        "asset_id" => $vbAction["asset_id"],
        "control_program" => $parametes['program'],
        "type" => "control_program"
      ];

      $actions = array();
      $actions[] = $spendAction;
      $actions[] = $vbAction;
      $actions[] = $controlAction;

      $parametes['password'] = 'baige';
      $res = self::getBytomClient()->buildTransaction($actions);
      $data = $res->getJSONDecodedBody();

      if($data["status"] == "success"){

        $res = self::getBytomClient()->signTransaction($parametes['password'], $data["data"]);
        $data = $res->getJSONDecodedBody();
        if($data["status"] == "success"){

          $res = self::getBytomClient()->submitTransaction($data["data"]["transaction"]["raw_transaction"]);
          $data = $res->getJSONDecodedBody();

          if($data["status"] == "success" && isset($data['data']['tx_id'])){
              return $data['data']['tx_id'];
          }
        } else {
            $response['message'] = $data['msg'];
        }
      } else {
          $response['message'] = $data['msg'];
      }

      Exception::logger(print_r($data, true), 1);

      return $response;
    }


    /**
     * pull contract infos
     * @param array $parametes
     * @return array|bool
     */
    public static function pullContract($parametes = [])
    {
        $response = ['success'=> false, 'message'=>'合约拉取失败'];

        if (!count($parametes) || !count($parametes['txid'])) {
            return $response;
        }

        $dataList = array();

        foreach ($parametes['txid'] as $tx_id) {
            //$id = "", $account_id = "", $detail = false
            $res = self::getBytomClient()->getTransaction($tx_id);
            $data = $res->getJSONDecodedBody();


            if($data["status"] != "success" || $data["data"]["block_height"] <= 0){
                Exception::logger(print_r($data, true), 1);
                continue;
            }

            foreach ($data["data"]["outputs"] as $outputsInfo) {
              if(strpos($outputsInfo["control_program"], "00") !== 0){
                  $dataList[$tx_id] = $outputsInfo["id"];
              }
            }
        }

        if (!count($dataList)) {
            return $response;
        }

        return $dataList;
    }

    /**
     * freed contract to bytom
     * @param array $parametes
     * @return array|integer
     */
    public static function freedContract($parametes = [])
    {
        $response = ['success'=> false, 'message'=>'合约解除失败'];
        if (!count($parametes)) {
            return $response;
        }

	    $parametes['password'] = 'baige';

        $byId = $parametes['byid'];
        $cid = $parametes['cid'];
        $password = $parametes['password'];
        $unlockKey = $parametes['unlockkey'];

        $api = self::BYTOM_HOST_URI.'/list-unspent-outputs';
        $xbhttpClient = new CurlHttpClient("");
        $params = ['id' => $cid, 'smart_contract' => true];
        $result = $xbhttpClient->post($api, $params);
        $data = $result->getJSONDecodedBody();

        if($data["status"] == "success"){
            $info = $data["data"][0];
            $spendAccount_tmp = [
              "account_id" => $byId,
              "amount" => 1000000,
              "asset_id" => "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff",
              "type" => "spend_account"
            ];

            $api = self::BYTOM_HOST_URI.'/list-addresses';
            $xbhttpClient = new CurlHttpClient("");

            $params = ['account_id' => $byId];

            $result = $xbhttpClient->post($api, $params);
            $data = $result->getJSONDecodedBody();

            if($data["status"] == "success"){

                $receiver = $data["data"][0];

                $vboxKey = "gam";
                $tmpKey = $vboxKey.$unlockKey;

                $arguments = array();
                $arguments[] = [
                  "type" => "string",
                  "raw_data" => [
                    "value" => bin2hex($tmpKey)
                  ]
                ];

                $vbAction = [
                  "output_id" => $cid,
                  "arguments" => $arguments,
                  "type" => "spend_account_unspent_output"
                ];

                $controlAction = [
                  "amount" => $info["amount"],
                  "asset_id" => $info["asset_id"],
                  "control_program" => $receiver["control_program"],
                  "type" => "control_program"
                ];

                $actions = array();
                $actions[] = $spendAccount_tmp;
                $actions[] = $vbAction;
                $actions[] = $controlAction;

                $res = self::getBytomClient()->buildTransaction($actions);
                $data = $res->getJSONDecodedBody();

                if($data["status"] == "success"){

                  $res = self::getBytomClient()->signTransaction($password, $data["data"]);
                  $data = $res->getJSONDecodedBody();
                  if($data["status"] == "success"){

                    $res = self::getBytomClient()->submitTransaction($data["data"]["transaction"]["raw_transaction"]);
                    $data = $res->getJSONDecodedBody();

                    if($data["status"] == "success"){
                        return $data['data']['tx_id'];
                    }
                  }
                }
            } else {
                $response['message'] = $data['msg'];
            }
        }

        $response['message'] = $data['msg'];

        Exception::logger(print_r($data, true), 1);

        return $response;
    }

}
