const byEvent = require("../controller/byEvent");

const router = require('koa-router')()

router.prefix('/api/v1')

/**
 * 创建账号
 */
router.get('/bytom/c_account',byEvent.createBYTom);

/*
  装平台币
*/
router.get('/bytom/t_accounts',byEvent.transferaccounts);

/*
创建合约
*/
router.get('/bytom/c_contract',byEvent.createContract);
/*
发布合约
*/
router.get('/bytom/p_contract',byEvent.pushContract);

/*
获取已发布的合约（未成交） 也就是没有接受的任务
*/
router.get('/bytom/getunspentoutput',byEvent.getUnspentOutput);

/*
解锁任务
*/
router.get('/bytom/unlockcontract',byEvent.unLockContract);


module.exports = router
