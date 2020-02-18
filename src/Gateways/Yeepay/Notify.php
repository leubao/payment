<?php

namespace Payment\Gateways\Yeepay;
use Payment\Exceptions\GatewayException;
use Payment\Helpers\ArrayUtil;
use Payment\Helpers\DataParser;
use Payment\Payment;
/**
 * Class Notify
 *
 * @package Payment\Gateways\Yeepay
 * @author  : jingzhou
 * @email   : xunzhou@leubao.com
 * @date    : 2020/2/14 20:54
 * @desc    :异步通知数据处理
 */
class Notify extends YeeBaseObject
{
    
    /**
     * @param array $requestParams
     *
     * @return mixed
     */
    protected function getBizContent(array $requestParams)
    {
        // TODO: Implement getBizContent() method.
    }
}