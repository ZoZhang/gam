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

    CONST BYTOM_HOST_URI = 'http://gam.zhaozhang.fr:9888';

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
        if (empty($username) || empty($password)) {
            return false;
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
            throw new \Exception(print_r($data, true));
        }

        return false;
    }

    /**
     * create contract to bytom
     * @param string $title
     * @param string $contract
     * @return array|string
     */
    public static function createContract($title = '')
    {
        if (empty($title)) {
            return false;
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

        Exception::logger(print_r($data, true), 1);

        return false;
    }

    /**
     * push contract to bytom
     * @param array $parametes
     * @return array|integer
     */
    public static function pushContract($parametes = [])
    {
      if (!count($parametes)) {
            return false;
      }

      $spendAction = [
        "account_id" => $parametes['byid'],
        "amount" => 1,
        "asset_id" => "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff",
        "type" => "spend_account"
      ];

      $vbAction = [
        "account_id" => $parametes['byid'],
        "amount" => 1,
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

      $res = self::getBytomClient()->buildTransaction($actions);
      $data = $res->getJSONDecodedBody();

      if($data["status"] == "success"){

        $res = self::getBytomClient()->signTransaction($parametes['password'], $data["data"]);
        $data = $res->getJSONDecodedBody();
        if($data["status"] == "success"){

          $res = self::getBytomClient()->submitTransaction($parametes['password'], $data["data"]["raw_transaction"]);
          $data = $res->getJSONDecodedBody();

          if($data["status"] == "success" && isset($data['tx_id'])){
              return $data['tx_id'];
          }
        }
      }

      Exception::logger(print_r($data, true), 1);

      return false;
    }


    /**
     * pull contract infos
     * @param array $parametes
     * @return array|bool
     */
    public static function pullContract($parametes = [])
    {
        if (!count($parametes)) {
            return false;
        }

        $dataList = array();
        $newIds = explode(',', $parametes['txid']);

        foreach ($newIds as $tx_id) {
            //$id = "", $account_id = "", $detail = false
            $res = self::getBytomClient()->getTransaction($tx_id);
            $data = $res->getJSONDecodedBody();
            if($data["status"] == "success"){

                foreach ($data["data"]["outputs"] as $outputsInfo) {
                    if(strpos($outputsInfo["control_program"], "00") === 0){

                    }else {
                        $dataList[$tx_id] = $outputsInfo["id"];
                    }
                }
            } else {
                Exception::logger(print_r($data, true), 1);
            }
        }

        if (!count($dataList)) {
            return false;
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
        if (!count($parametes)) {
            return false;
        }

        $byId = $parametes['byid'];
        $cid = $parametes['cid'];
        $password = $parametes['password'];
        $unlockKey = $parametes['unlockkey'];

        $res = self::getBytomClient()->listUnspentOutPuts($cid);
        $data = $res->getJSONDecodedBody();
        if($data["status"] == "success"){
            $info = $data["data"][0];
            $spendAccount_tmp = [
              "account_id" => $byId,
              "amount" => 1,
              "asset_id" => "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff",
              "type" => "spend_account"
            ];

            $res = self::getBytomClient()->listAddresses($spendAccount_tmp["account_id"],$spendAccount_tmp["asset_id"]);
            $data = $res->getJSONDecodedBody();
            if($data["status"] == "success"){

                $receiver = $data["data"][0];

                $vboxKey = "gam";
                $tmpKey = $vboxKey.$unlockKey;

                $arguments = array();
                $arguments[] = [
                  "type" => "string",
                  "raw_data" => [
                    "value" => $tmpKey
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

                  $res = self::getBytomClient()->signTransaction($password,$data["data"]);
                  $data = $res->getJSONDecodedBody();
                  if($data["status"] == "success"){

                    $res = self::getBytomClient()->submitTransaction($password,$data["data"]["raw_transaction"]);
                    $data = $res->getJSONDecodedBody();

                    if($data["status"] == "success"){
                        return $data['tx_id'];
                    }
                  }
                }
            }
        }

        Exception::logger(print_r($data, true), 1);

        return false;
    }

}
