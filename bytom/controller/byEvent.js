
const bytom = require('bytom-sdk');

const url = 'http://localhost:9888';

const accessToken = '';

const uuid = require('uuid')

const jsSHA = require("jssha");

class ByEvent {


    //创建账号-对应链上也需要创建
    /*
      创建账号
    */
    static async createBYTom(ctx) {

    try {
        let body = ctx.request.query;

        let username = body.username
        let password = body.password

        const client = new bytom.Client(url, accessToken);

        let mockHsmKey;
        let byId;

        const getObj = await client.keys.listAll().then(keys => {
           mockHsmKey = keys[0]
        }).then(() => {//创建账号
          return client.accounts.create({
            alias: username, root_xpubs: [mockHsmKey.xpub], quorum: 1
          }).then(account => {//创建地址
             byId = account.id;
             return client.accounts.createReceiver({
                account_alias: account.alias
            }).then(address => {

              return {
                  code: 200,
                  data:{
                    byId:byId
                  },
                  msg: '创建成功'
              };

            }).catch(err => {

              return {
                  code: -1,
                  msg: err
              };

            });
          }).catch(err => {

            return {
                code: -1,
                msg: err
            };

          });
        });

        ctx.body = getObj;

      } catch (e) {
        ctx.body = {
          code: 404,
          msg:"异常",
          err:e
        }
      }
    }

    /*
    装平台币
    */
    static async transferaccounts(ctx){

      try {
        // let body = ctx.request.query;
        //
        // let address = body.address
        //
        // const client = new bytom.Client(url, accessToken);
        //
        // const vbAction = {
        //   account_id: "0V2F8EONG0A02",
        //   amount: 10000000000,
        //   asset_id: "ae46a56162568e64dd173f2a5cfa443b357f8a1720e4da9c728356cef69293c1",
        // }
        //
        // const controlAction = {
        //     amount: vbAction.amount,
        //     asset_id: vbAction.asset_id,
        //     address: address
        // }
        //
        // const buildPromise = client.transactions.build(builder => {
        //     builder.spendFromAccount(spendAccount)
        //     builder.spendFromAccount(vbAction)
        //     builder.controlWithAddress(controlAction)
        // })
        //
        // //签署交易
        // const signPromise = buildPromise.then(transactionTemplate => {
        //     return client.transactions.sign({
        //         transaction: transactionTemplate,
        //         password: 'baige'
        //     })
        // })
        // //最后，将签名的事务提交给bytom网络
        // const getObj = await signPromise.then(signed => {
        //     return client.transactions.submit(signed.transaction.raw_transaction)
        // })

        ctx.body = {
          code: 200,
          msg:"等待完善",
        }

      } catch (e) {
        ctx.body = {
          code: 404,
          msg:"异常",
          err:e
        }
      }
    }

    /*
    创建合约
    */
    static async createContract(ctx){

        try {

          //秘钥合约
          let contractStr = "contract SecretContract(hash: Hash) locks valueAmount of valueAsset { clause vbox(string: String) { verify sha3(string) == hash unlock valueAmount of valueAsset }}"


          let body = ctx.request.query;
          let unlockKey = body.unlockkey;

          let vboxKey = "vbox";

          let tmpKey = vboxKey + unlockKey;

          var shaObj = new jsSHA("SHA3-256", "TEXT");
          shaObj.update(tmpKey);

          var hash = shaObj.getHash("HEX");

          const client = new bytom.Client(url, accessToken);

          //生成合约
          let result = await client.connection.request('/compile', {
            "contract":contractStr,
            "args": [
              {
                "string":hash
              }
            ]
          });

          ctx.body = {
            code: 200,
            msg:"创建成功",
            data: {
              program:result.program
            }
          }

        } catch (e) {
          ctx.body = {
            code: 404,
            msg:"异常",
            err:e
          }
        }
    }
    /*
      push合约
    */

    static async pushContract(ctx){

      try {

        let body = ctx.request.query;

        let program = body.program;
        let byId = body.byId;
        let password = body.password;

        if (program && byId){

            const client = new bytom.Client(url, accessToken);


            const spendAction = {
              account_id: byId,
              amount: 1000000,
              asset_id: "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff",
            }

            //部署合约 默认支持一种资产
            const vbAction = {
              account_id: byId,
              amount: 10000000000,
              asset_id: "31434830dd7af31d7bb2aed3942cbc15f5ad78c438c11ff52caef10a05bef40c",
            }

            const controlAction = {
                amount: vbAction.amount,
                asset_id: vbAction.asset_id,
                control_program:program
            }

            const buildPromise = client.transactions.build(builder => {
                builder.spendFromAccount(spendAction)
                builder.spendFromAccount(vbAction)
                builder.controlWithControlProgram(controlAction)
            })

            //签署交易
            const signPromise = buildPromise.then(transactionTemplate => {
                return client.transactions.sign({
                    transaction: transactionTemplate,
                    password: password
                })
            })
            //最后，将签名的事务提交给bytom网络
            const data = await signPromise.then(signed => {
                return client.transactions.submit(signed.transaction.raw_transaction)
            })

            ctx.body = {
              code: 200,
              data,
              msg:"合约ok",
            }

        }else {
          ctx.body = {
            code: -1,
            msg:"program 失败",
          }
        }

      } catch (e) {
        ctx.body = {
          code: 404,
          msg:"异常",
          err:e
        }
      }
    }


    /*
    获取获取tx_id是否被确认
    */
    static async getUnspentOutput(ctx){

        try {

          //ids
          let body = ctx.request.query;

          let ids = body.ids;
          let newIds = ids.split(",");

          let newList = [];

          const client = new bytom.Client(url, accessToken);

          for(let index=0;index<newIds.length;index++){
            let tx_id = newIds[index];
            const contractInfo = await client.transactions.list({
              id:tx_id,
              unconfirmed: true,
              detail:true
            }).then(obj => {
                return obj;
            });
            if(contractInfo){
                for(let j=0;j<contractInfo[0].outputs.length;j++){
                  let outObj = contractInfo[0].outputs[j];
                  if(outObj.control_program.startsWith("20f")){//表示合约
                      if(outObj.id){
                          newList.push({
                            c_id:outObj.id,
                            tx_id:tx_id
                          });
                      }
                  }
                }
            }
          }

          ctx.body = {
            code: 200,
            msg:"获取ok",
            data:newList
          }

        } catch (e) {
          ctx.body = {
            code: 404,
            msg:"异常",
            err:e
          }
        }
    }

    /*
      任务完成了 解锁资产
    */
    static async unLockContract(ctx){
        try {

          let body = ctx.request.query;

          let ouput_id = body.c_id;
          let byId = body.byId;
          let unlockkey = body.unlockkey;
          let password = body.password;

          let vboxKey = "vbox";
          let tmpKey = vboxKey + unlockkey;
          var shaObj = new jsSHA("SHA3-256", "TEXT");
          shaObj.update(tmpKey);
          var hash = shaObj.getHash("HEX");//密码

          const client = new bytom.Client(url, accessToken);

          const info = await client.unspentOutputs.list({
            id: ouput_id,
            smart_contract: true
          }).then(info => {
              return info[0];
          });

          //解锁合约的人
          let spendAccount_tmp = {
            account_id: byId,
            amount: 1000000,
            asset_id: "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff",
          }

          const receiver = await client.accounts.listAddresses({
            account_id:spendAccount_tmp.account_id
          }).then(resp => {
            if (resp.length === 0) {
                return this.createReceiver(accountId);
            } else {
                return resp[0];
            }
          });

          const vbAction = {
            output_id: ouput_id,
            arguments: [
              {
                type:"data",
                raw_data:{
                  value:hash
                }
              }
            ],
          }

          const controlAction = {
              amount: info.amount,
              asset_id: info.asset_id,
              control_program:receiver.control_program
          }

          const buildPromise = client.transactions.build(builder => {
              builder.spendFromAccount(spendAccount_tmp)
              builder.spendAccountUnspentOutput(vbAction)
              builder.controlWithControlProgram(controlAction)
          })

          //签署交易
          const signPromise = buildPromise.then(transactionTemplate => {
              return client.transactions.sign({
                  transaction: transactionTemplate,
                  password: password
              })
          })
          //最后，将签名的事务提交给bytom网络
          const data = await signPromise.then(signed => {
              return client.transactions.submit(signed.transaction.raw_transaction)
          })

          ctx.body = {
            code: 200,
            msg:"解锁成功",
            data
          }

        } catch (e) {
          ctx.body = {
            code: 404,
            msg:"异常",
            err:e
          }
        }
    }
}

module.exports = ByEvent;
