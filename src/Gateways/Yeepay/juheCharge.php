<?php


namespace Payment\Gateways\Yeepay;


use Payment\Contracts\IGatewayRequest;
use Payment\Exceptions\GatewayException;
use Payment\Helpers\ArrayUtil;

/**
 * Class juheCharge
 *
 * @package Payment\Gateways\Yeepay
 * @author  : jingzhou
 * @email   : xunzhou@leubao.com
 * @date    : 2020/2/14 21:01
 * @desc    : 聚合支付通过易宝完成商户小程序、公众号、支付宝生活号、用户主扫收款服务
 */
class juheCharge extends YeeBaseObject implements IGatewayRequest
{
    const METHOD = '/rest/v1.0/at-cloud-pay/fe-order/create';

    /**
     * @param array $requestParams
     *
     * @return mixed
     */
    protected function getBizContent(array $requestParams)
    {
        $nowTime    = time();
    
        $timeoutExp = '';
        $timeExpire = intval($requestParams['time_expire']);
        if (!empty($timeExpire)) {
            $expire                      = floor(($timeExpire - time()) / 60);
            ($expire > 0) && $timeoutExp = $expire . 'm';// 超时时间 统一使用分钟计算
        }
    
        $bizContent = [
            'customerBizRequestNo' => $requestParams['trade_no'] ?? '',
            'product'              => $requestParams['product'] ?? 'MINI_PROGRAM',//MINI_PROGRAM ：微信小程序支付 ZF_GZH : 公众号支付ZFB_SHH: 支付宝生活号支付 YHSM:用户扫码 WECHAT_SDK:SDK 支付
            'productService'       => $requestParams['product_service'],// MINI_PROGRAM ：微信小程序支付 ZF_GZH : 公 众 号 支付ZFB_SHH: 支 付 宝 生活号支付 YHSM:用户扫码 WECHAT_SDK:S DK 支付
            'payWay'               => $requestParams['pay_way'],//用户扫码API 微信:WECHAT 支付宝:ALIPAY 京东钱包:JD 银联:UPOP qq 钱包:QQ
            'amount'               => $requestParams['amount'] ?? '',
            'orderDate'            => date('Y-m-d H:i:s', $nowTime),
            'timeoutExpress'       => $timeExpire,
            'receiverCallbackUrl'  => $requestParams['receiver_url'] ?? '',//收单方回调URL
            'requestCallbackUrl'   => $requestParams['request_url'] ?? '',//请求方回调URL
            'goodsName'            => $requestParams['subject'] ?? '',
            'goodsCat'             => $requestParams['goods_cat'] ?? '',//商品类别
            'goodsDesc'            => $requestParams['body'] ?? '',
            'goodsExtInfo'         => $requestParams['goods_ext_info'] ?? '',//商品扩展字段
            'currency'             => 'CNY',
            'bankCode'             => 'OFFLINE',//银行编码区分线上线下 ONLINE(线上) OFFLINE(线下) 暂 时 只 支 持 OFFLINE(线下)
            'appId'                => $requestParams['app_id'] ?? '', //微 信 小 程 序 appId 公 众 号 APPId 生 活 号 userId
            'oepnId'               => $requestParams['open_id'] ?? '',  //微 信 小 程 序 用 户 openId 公 众 号 openId 生 活 号 userId
            'memo'                 => $requestParams['memo'] ?? '',//自定义对账字段
            'extendMap'            => $requestParams['extend_map'] ?? '',//风控参数
            'accountWay'           => $requestParams['account_way'] ?? '',//入账方式COMMON：普通/ 担保SPLIT：分账 默认：COM
            'assurePeriod'         => $requestParams['assurePeriod'] ?? '', //担保有效期 入账方式为担保时必填默认365天
            'accountType'          => $requestParams['account_type'] ?? '',//入账类型 REAL_TIME：实时入 账ASSURE：担保交易 默认：REAL_TIME
            'customerRequestNo'    => $requestParams['serial_no'] ?? '',//业务单号
            'payerIp'              => $requestParams['client_ip'] ?? '',
            'splitInfo'            => $requestParams['split_info'] ?? '',//分账信息
        ];
        $bizContent = ArrayUtil::paraFilter($bizContent);
    
        return $bizContent;
    }
    
    /**
     * 获取第三方返回结果
     *
     * @param array $requestParams
     *
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        try {
            $params = $this->buildParams(self::METHOD, $requestParams);
            return http_build_query($params);
        } catch (GatewayException $e) {
            throw $e;
        }
    }
}