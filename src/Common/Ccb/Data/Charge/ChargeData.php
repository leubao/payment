<?php
namespace Payment\Common\Ccb\Data\Charge;

use Payment\Common\Ccb\Data\CmbBaseData;
use Payment\Common\CcbConfig;
use Payment\Common\PayException;
use Payment\Config;

/**
 * Created by Sublime
 * User: zhoujing
 * Date: 2018/5/8
 * Time: 下午22:38
 *
 * @property string $date 订单日期,格式：yyyyMMdd
 * @property string $order_no  订单号, 10位数字，由商户生成，一天内不能重复。订单日期+订单号唯一定位一笔订单。
 * @property string $amount  金额, 格式：xxxx.xx  固定两位小数，最大11位整数
 * @property integer $timeout_express  过期时间
 * @property string $lon 经度，商户app获取的手机定位数据，如30.949505
 * @property string $lat 纬度，商户app获取的手机定位数据，如50.949506
 *
 */
class ChargeData extends CcbBaseData
{
    /**
     * 发送请求
     */
    protected function checkDataParam()
    {
        parent::checkDataParam();
        $amount = $this->amount;
      
        // 订单号交给支付系统自己检查

        // 检查金额不能低于0.01
        if (bccomp($amount, Config::PAY_MIN_FEE, 2) === -1) {
            throw new PayException('支付金额不能低于 ' . Config::PAY_MIN_FEE . ' 元');
        }

        // 设置ip地址
        $clientIp = $this->client_ip;
        if (empty($clientIp)) {
            $this->client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        }

        $timeExpire = $this->timeout_express;
        if (! empty($timeExpire)) {
            $express = floor(($timeExpire - strtotime($this->dateTime)) / 60);

            if ($express > CcbConfig::MAX_EXPIRE_TIME || $express < 0) {// 招商规定
                $this->timeout_express = CcbConfig::MAX_EXPIRE_TIME;
            } else {
                $this->timeout_express = $express;
            }
        }
    }

    /**
     * 请求数据
     */
    protected function getReqData()
    {
        $reqData = [

            'ORDERID'       =>  $this->order_no,//定单号
            'PAYMENT'       =>  $this->amount,//付款金额
            'CURCODE'       =>  '01',//币种 01人民币
            'TXCODE'        =>  ,//交易码
            'REMARK1'       =>  ,//备注1
            'REMARK2'       =>  ,//备注2
            'TYPE'          =>  '1',//防钓鱼
            'GATEWAY'       =>  ,//网关类型
            'CLIENTIP'      =>  $this->client_ip,//客户端IP
            'REGINFO'       =>  ,//注册信息
            'PROINFO'       =>  ,//商品信息
            'REFERER'       =>  ,
            'INSTALLNUM'    =>  1,//分期期数 不允许分期 TODO
            'TIMEOUT'       =>  ,//订单超时时间YYYYMMDDHHMMSS
            'MAC'           =>  
        ];

        // 这里不能进行过滤空值，招商的空值也要加入签名中
        return $reqData;
    }
}
