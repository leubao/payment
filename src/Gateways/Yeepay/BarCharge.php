<?php


namespace Payment\Gateways\Yeepay;
use Payment\Contracts\IGatewayRequest;
use Payment\Exceptions\GatewayException;
use Payment\Helpers\ArrayUtil;
use Payment\Payment;

/**
 * Class BarCharge
 *
 * @package Payment\Gateways\Yeepay
 * @author  : jingzhou
 * @email   : xunzhou@leubao.com
 * @date    : 2020/2/14 17:43
 * @desc    : 收银员使用扫码设备读取用户手机支付宝“付款码”或微信付款码
 */

class BarCharge extends YeeBaseObject implements IGatewayRequest
{
    const METHOD = '/rest/v1.0/at-cloud-pay/scan-user-code/order-pay';
    
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
            'customerNo'           => $requestParams['customerNo'] ?? '',
            'customerBizRequestNo' => $requestParams['trade_no'] ?? '',
            'amount'               => $requestParams['amount'] ?? '',
            'orderDate'            => date('Y-m-d H:i:s', $nowTime),
            'timeoutExpress'       => $timeExpire,
            'receiverCallbackUrl'  => $requestParams['receiver_url'] ?? '',//收单方回调URL
            'requestCallbackUrl'   => $requestParams['request_url'] ?? '',//请求方回调URL
            'goodsName'            => $requestParams['subject'] ?? '',
            'goodsCat'             => $requestParams['goods_cat'] ?? '',//商品类别
            'goodsDesc'            => $requestParams['body'] ?? '',
            'goodsExtInfo'         => $requestParams['goods_ext_info'] ?? '',//商品扩展字段
            'memo'                 => $requestParams['memo'] ?? '',//自定义对账字段
            'extendMap'            => $requestParams['extend_map'] ?? '',//风控参数
            'accountWay'           => $requestParams['account_way'] ?? '',//入账方式COMMON：普通/ 担保SPLIT：分账 默认：COM
            'assurePeriod'         => $requestParams['assurePeriod'] ?? '', //担保有效期 入账方式为担保时必填默认365天
            'accountType'          => $requestParams['account_type'] ?? '',//入账类型 REAL_TIME：实时入 账ASSURE：担保交易 默认：REAL_TIME
            'customerRequestNo'    => $requestParams['serial_no'] ?? '',//业务单号
            'payerIp'              => $requestParams['client_ip'] ?? '',
            'splitInfo'            => $requestParams['split_info'] ?? '',//分账信息
            'payEmpowerNo'         => $requestParams['auth_code'] ?? '',//支付授权码
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
            $ret    = $this->get($this->gatewayUrl, $params);
            $retArr = json_decode($ret, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new GatewayException(sprintf('format bar data get error, [%s]', json_last_error_msg()), Payment::FORMAT_DATA_ERR, $ret);
            }
        
            $content = $retArr['alipay_trade_pay_response'];
            if ($content['code'] !== self::REQ_SUC) {
                throw new GatewayException(sprintf('request get failed, msg[%s], sub_msg[%s]', $content['msg'], $content['sub_msg']), Payment::SIGN_ERR, $content);
            }
        
            $signFlag = $this->verifySign($content, $retArr['sign']);
            if (!$signFlag) {
                throw new GatewayException('check sign failed', Payment::SIGN_ERR, $retArr);
            }
        
            return $content;
        } catch (GatewayException $e) {
            throw $e;
        }
    }
}