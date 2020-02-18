<?php

namespace Payment\Gateways\Yeepay;

use Payment\Exceptions\GatewayException;
use Payment\Helpers\ArrayUtil;
use Payment\Helpers\DataParser;
use Payment\Helpers\Rsa2Encrypt;
use Payment\Helpers\RsaEncrypt;
use Payment\Helpers\StrUtil;
use Payment\Payment;
use Payment\Supports\HttpRequest;
use Payment\Supports\BaseObject;

/**
 * Class YeeBaseObject
 *
 * @package Payment\Gateways\Yeepay
 * @author  : jingzhou
 * @email   : xunzhou@leubao.com
 * @date    : 2020/2/14 17:45
 * @desc    : 易宝支付业务基础类
 */

abstract class YeeBaseObject extends BaseObject
{
    use HttpRequest;
    
    const REQ_SUC = '10000';
    
    protected $config;
    
    protected $httpMethod = 'POST';
    protected $method;
    protected $version = "3.1.3";
    protected $signAlg = "sha256";
    
    /**
     * 商户编号，易宝商户可不注册开放应用(获取appKey)也可直接调用API
     * @var string
     */
    protected $mchId = '';
    
    /**
     * 可支持不同请求使用不同的appKey及secretKey
     * @var string
     */
    protected $appKey = '';
    
    protected $headers = array();
    protected $paramMap = array();
    protected $fileMap = array();
    protected $jsonParam;
    protected $ignoreSignParams = array('sign');
    
    protected $requestId;
    
    /**
     * 连接超时时间
     */
    protected $connectTimeout = 30000;
    
    /**
     * 读取返回结果超时
     */
    protected $readTimeout = 60000;
    
    
    /**
     * 报文是否加密，如果请求加密，则响应也加密，需做解密处理
     */
    protected $encrypt = false;
    /**
     * 可支持不同请求使用不同的appKey及secretKey,secretKey只用于本地签名，不会被提交
     * @var string
     */
    protected $privateKey = '';
    
    /**
     * 易宝公钥--正式环境也用此公钥，不用改
     * 可支持不同请求使用不同的appKey及secretKey、serverRoot,secretKey只用于本地签名，不会被提交
     * @var string
     */
    protected $publicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6p0XWjscY+gsyqKRhw9MeLsEmhFdBRhT2emOck/F1Omw38ZWhJxh9kDfs5HzFJMrVozgU+SJFDONxs8UB0wMILKRmqfLcfClG9MyCNuJkkfm0HFQv1hRGdOvZPXj3Bckuwa7FrEXBRYUhK7vJ40afumspthmse6bs6mZxNn/mALZ2X07uznOrrc2rk41Y2HftduxZw6T4EmtWuN2x4CZ8gwSyPAW5ZzZJLQ6tZDojBK4GZTAGhnn3bg5bBsBlw2+FLkCQBuDsJVsFPiGh/b6K/+zGTvWyUcu+LUj2MejYQELDO3i2vQXVDk7lVi2/TcUYefvIcssnzsfCfjaorxsuwIDAQAB\',
    ';
    
    /**
     * 业务结果是否签名，默认不签名
     */
    protected $signRet = false;
    
    protected $serverRoot;
    protected $downrequest;
    
    /**
     * @var string
     */
    protected $protVersion = 'yop-auth-v2';
    
    /**
     * @var int
     */
    protected $expiredSeconds = 1800;
    
    /**
     * @var string
     */
    protected $gatewayUrl = '';
    
    /**
     * @var bool
     */
    protected $isSandbox = false;
    
    /**
     * @var bool
     */
    protected $returnRaw = false;
    
    /**
     * YeeBaseObject constructor.
     *
     * @throws GatewayException
     */
    public function __construct()
    {
        $this->mchId = self::$config->get('mch_id', '');
        $this->appKey = self::$config->get('app_key', '');
        
        $this->requestId = session_create_id();
        
        
        
        
        $this->isSandbox = self::$config->get('use_sandbox', false);
        $this->returnRaw = self::$config->get('return_raw', false);
        
        // 新版本，需要提供独立的支付宝公钥信息。每一个应用，公钥都不相同
        $rsaPublicKey = self::$config->get('ali_public_key', '');
        if ($rsaPublicKey) {
            $this->publicKey = StrUtil::getRsaKeyValue($rsaPublicKey, 'public');
        }
        if (empty($this->publicKey)) {
            throw new GatewayException('please set ali public key', Payment::PARAMS_ERR);
        }
        
        // 初始 RSA私钥文件 需要检查该文件是否存在
        $rsaPrivateKey = self::$config->get('rsa_private_key', '');
        if ($rsaPrivateKey) {
            $this->privateKey = StrUtil::getRsaKeyValue($rsaPrivateKey, 'private');
        }
        if (empty($this->privateKey)) {
            throw new GatewayException('please set ali private key', Payment::PARAMS_ERR);
        }
        
        // 初始 易宝网关地址
        $this->gatewayUrl = 'https://openapi.yeepay.com/yop-center';
        if ($this->isSandbox) {
            $this->gatewayUrl = 'https://openapi.alipaydev.com/gateway.do';
        }
    }
    
    /**
     * @param string $signType
     * @param string $signStr
     * @return string
     * @throws GatewayException
     */
    protected function makeSign(string $signType, string $signStr)
    {
        $signType = strtoupper($signType);
        try {
            switch ($signType) {
                case 'RSA':
                    $rsa = new RsaEncrypt($this->privateKey);
                    
                    $sign = $rsa->encrypt($signStr);
                    break;
                case 'RSA2':
                    $rsa = new Rsa2Encrypt($this->privateKey);
                    
                    $sign = $rsa->encrypt($signStr);
                    break;
                default:
                    throw new GatewayException(sprintf('[%s] sign type not support', $signType), Payment::PARAMS_ERR);
            }
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException(sprintf('sign error, sign type is [%s]. msg: [%s]', $signType, $e->getMessage()), Payment::SIGN_ERR);
        }
        
        return $sign;
    }
    
    /**
     * @param array $data
     * @param string $sign
     * @return bool
     * @throws GatewayException
     */
    protected function verifySign(array $data, string $sign)
    {
        $signType = strtoupper(self::$config->get('sign_type', ''));
        $preStr   = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        try {
            if ($signType === 'RSA') {// 使用RSA
                $rsa = new RsaEncrypt($this->publicKey);
                return $rsa->rsaVerify($preStr, $sign);
            } elseif ($signType === 'RSA2') {// 使用rsa2方式
                $rsa = new Rsa2Encrypt($this->publicKey);
                return $rsa->rsaVerify($preStr, $sign);
            }
            throw new GatewayException(sprintf('[%s] sign type not support', $signType), Payment::PARAMS_ERR);
        } catch (\Exception $e) {
            throw new GatewayException(sprintf('check ali pay sign failed, sign type is [%s]', $signType), Payment::SIGN_ERR, $data);
        }
    }
    
    /**
     * 针对异步通知的验证签名
     * @param array $data
     * @param string $sign
     * @param string $signType
     * @return bool
     * @throws GatewayException
     */
    protected function verifySignForASync(array $data, string $sign, string $signType)
    {
        $params = ArrayUtil::arraySort($data);
        
        try {
            $preStr = ArrayUtil::createLinkString($params);
            
            if ($signType === 'RSA') {// 使用RSA
                $rsa = new RsaEncrypt($this->publicKey);
                return $rsa->rsaVerify($preStr, $sign);
            } elseif ($signType === 'RSA2') {// 使用rsa2方式
                $rsa = new Rsa2Encrypt($this->publicKey);
                return $rsa->rsaVerify($preStr, $sign);
            }
            throw new GatewayException(sprintf('[%s] sign type not support', $signType), Payment::PARAMS_ERR);
        } catch (\Exception $e) {
            throw new GatewayException(sprintf('check ali pay sign failed, sign type is [%s]', $signType), Payment::SIGN_ERR, $data);
        }
    }
    
    /**
     * 生成请求参数
     * @param array $requestParams
     * @return string
     * @throws GatewayException
     */
    protected function buildParams(array $requestParams = [])
    {
        $params = [
            'customerNo' => $this->mchId,//系统商编号=上级商编
        ];
        $params = $this->changeKeyName($params);
        
        if (!empty($requestParams)) {
            $selfParams = $this->getSelfParams($requestParams);
            
            if (is_array($selfParams) && !empty($selfParams)) {
                $params = array_merge($params, $selfParams);
            }
        }
        
        $params = ArrayUtil::paraFilter($params);
        $params = ArrayUtil::arraySort($params);
        
        try {
            $signStr        = ArrayUtil::createLinkstring($params);
            $params['sign'] = $this->makeSign($signStr);
        } catch (\Exception $e) {
            throw new GatewayException($e->getMessage(), Payment::PARAMS_ERR);
        }
        
        $xmlData = DataParser::toXml($params);
        if ($xmlData === false) {
            throw new GatewayException('error generating xml', Payment::FORMAT_DATA_ERR);
        }
        
        return $xmlData;
    }
    
    /**
     *
     */
    private function getHeaders(array $requestParams){
    
        $params = [
            'customerNo' => $this->mchId,//系统商编号=上级商编
        ];
        $params = $this->changeKeyName($params);
    
        if (!empty($requestParams)) {
            $selfParams = $this->getSelfParams($requestParams);
        
            if (is_array($selfParams) && !empty($selfParams)) {
                $params = array_merge($params, $selfParams);
            }
        }
    
        $params = ArrayUtil::paraFilter($params);
        $params = ArrayUtil::arraySort($params);
        
        
        //获取APPKEY
        if (empty($this->appKey)) {
            //当appkey 为空时获取上号号
            $this->appKey = $this->mchId;
        }
        if (empty($this->appKey)) {
            throw new GatewayException('appKey 与 mch_id 不能同时为空', Payment::PARAMS_ERR);
        }
        
        $headers = [];
    
        $headers['x-yop-appkey'] = $this->appKey;//应用标识appkey;
        $headers['x-yop-request-id'] = $requestParams['requestId'];
    
        date_default_timezone_set('PRC');
        $dataTime = new DateTime();
        $timestamp = $dataTime->format(DateTime::ISO8601); // Works the same since const ISO8601 = "Y-m-d\TH:i:sO"
        
        $authString = $this->protVersion . "/" . $this->appKey . "/" . $timestamp . "/" . $this->expiredSeconds;
        
        //设置参与签名的header头
        $headersToSignSet = ['x-yop-request-id'];
        
        if (!empty($this->mchId)) {
            $headers['x-yop-customerid'] = $this->appKey;
            array_push($headersToSignSet, "x-yop-customerid");
        }
        $headersToSign = ArrayUtil::returnArrKeys($headers, $headersToSignSet);
        $strHeader = $this->getHeaderStr($headersToSign);
        
        $canonicalRequest = $authString . "\n" . $this->httpMethod . "\n" . $canonicalURI . "\n" . $params . "\n" . $strHeader;
    
        $headers['Authorization'] = "YOP-RSA2048-SHA256 " . $protocolVersion . "/" . $appKey . "/" . $timestamp . "/" . $EXPIRED_SECONDS . "/" . $signedHeaders . "/" . $signToBase64;
    }
    
    /**
     * @param array $headers
     *
     * @return string
     */
    function getSignHeader(array $headers = []){
        if (empty($headers)) {
            return "";
        }
    
        $headerStrings = array();
    
        foreach ($headers as $key => $value) {
            if ($key == null) {
                continue;
            }
            if ($value == null) {
                $value = "";
            }
            $key = HttpUtils::normalize(strtolower(trim($key)));
            $value = HttpUtils::normalize(trim($value));
            array_push($headerStrings, $key . ':' . $value);
        }
    
        sort($headerStrings);
        $StrQuery = "";
    
        foreach ($headerStrings as $kv) {
            $StrQuery .= strlen($StrQuery) == 0 ? "" : "\n";
            $StrQuery .= $kv;
        }
    
        return $StrQuery;
    }
    
    /**
     * 获取基础数据
     * @param string $method
     * @param array $bizContent
     * @return array
     */
    private function getBaseData(string $method, array $bizContent)
    {
        $requestData = [
            'app_id'     => self::$config->get('app_id', ''),
            'method'     => $method,
            'format'     => 'JSON',
            'return_url' => self::$config->get('return_url', ''),
            'charset'    => 'utf-8',
            'sign_type'  => self::$config->get('sign_type', ''),
            'timestamp'  => date('Y-m-d H:i:s'),
            'version'    => '1.0',
            'notify_url' => self::$config->get('notify_url', ''),
            // 'app_auth_token' => '', // 暂时不用
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE),
        ];
        return ArrayUtil::arraySort($requestData);
    }
    
    /**
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function getBizContent(array $requestParams);
    /**
     * 请求易宝支付的api
     * @param string $method
     * @param array $requestParams
     * @return array|false
     * @throws GatewayException
     */
    protected function requestYEBApi(string $method, array $requestParams)
    {
        $this->methodName = $method;
        try {
            $xmlData = $this->buildParams($requestParams);
            $url     = sprintf($this->gatewayUrl, $method);
            
            $this->setHttpOptions($this->getCertOptions());
            $resXml = $this->postXML($url, $xmlData);
            if (in_array($method, ['pay/downloadbill', 'pay/downloadfundflow'])) {
                return $resXml;
            }
            
            $resArr = DataParser::toArray($resXml);
            if (!is_array($resArr) || $resArr['return_code'] !== self::REQ_SUC) {
                throw new GatewayException($this->getErrorMsg($resArr), Payment::GATEWAY_REFUSE, $resArr);
            } elseif (isset($resArr['result_code']) && $resArr['result_code'] !== self::REQ_SUC) {
                throw new GatewayException(sprintf('code:%d, desc:%s', $resArr['err_code'], $resArr['err_code_des']), Payment::GATEWAY_CHECK_FAILED, $resArr);
            }
            
            if (isset($resArr['sign']) && $this->verifySign($resArr) === false) {
                throw new GatewayException('check return data sign failed', Payment::SIGN_ERR, $resArr);
            }
            
            return $resArr;
        } catch (GatewayException $e) {
            throw $e;
        }
    }
    
    public static function SignRsaParameter($methodOrUri, $YopRequest)
    {
        $appKey = $YopRequest->{$YopRequest->config->APP_KEY};
        if (empty($appKey)) {
            $appKey = $YopRequest->config->CUSTOMER_NO;
            $YopRequest->removeParam($YopRequest->config->APP_KEY);
        }
        if (empty($appKey)) {
            error_log("appKey 与 customerNo 不能同时为空");
        }
        
        date_default_timezone_set('PRC');
        $dataTime = new DateTime();
        $timestamp = $dataTime->format(DateTime::ISO8601); // Works the same since const ISO8601 = "Y-m-d\TH:i:sO"
        
        //构建header
        $headers = array();
        
        $headers['x-yop-appkey'] = $YopRequest->appKey;
        $headers['x-yop-request-id'] = $YopRequest->requestId;
        //接口版本
        $protocolVersion = "yop-auth-v2";
        $EXPIRED_SECONDS = "1800";
        
        $authString = $protocolVersion . "/" . $appKey . "/" . $timestamp . "/" . $EXPIRED_SECONDS;
        
        $headersToSignSet = array();
        array_push($headersToSignSet, "x-yop-request-id");
        
        $appKey = $YopRequest->{$YopRequest->config->APP_KEY};
        
        if (!StringUtils::isBlank($YopRequest->config->CUSTOMER_NO)) {
            $headers['x-yop-customerid'] = $appKey;
            array_push($headersToSignSet, "x-yop-customerid");
        }
        
        // Formatting the URL with signing protocol.
        $canonicalURI = HttpUtils::getCanonicalURIPath($methodOrUri);
        
        // Formatting the query string with signing protocol.
        $canonicalQueryString = YopRsaClient::getCanonicalQueryString($YopRequest, true);
        
        // Sorted the headers should be signed from the request.
        $headersToSign = YopRsaClient::getHeadersToSign($headers, $headersToSignSet);
        
        // Formatting the headers from the request based on signing protocol.
        $canonicalHeader = YopRsaClient::getCanonicalHeaders($headersToSign);
        
        $signedHeaders = "";
        if ($headersToSignSet != null) {
            foreach ($headersToSign as $key => $value) {
                $signedHeaders .= strlen($signedHeaders) == 0 ? "" : ";";
                $signedHeaders .= $key;
            }
            $signedHeaders = strtolower($signedHeaders);
        }
        
        $canonicalRequest = $authString . "\n" . $YopRequest->httpMethod . "\n" . $canonicalURI . "\n" . $canonicalQueryString . "\n" . $canonicalHeader;
        
        // Signing the canonical request using key with sha-256 algorithm.
        
        if (empty($YopRequest->secretKey)) {
            error_log("secretKey must be specified");
        }
        
        extension_loaded('openssl') or die('php需要openssl扩展支持');
        
        $private_key = $YopRequest->secretKey;
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($private_key, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $privateKey = openssl_pkey_get_private($private_key);// 提取私钥
        ($privateKey) or die('密钥不可用');
        
        $signToBase64 = "";
        // echo "tyuiop".$canonicalRequest;
        openssl_sign($canonicalRequest, $encode_data, $privateKey, "SHA256");
        
        openssl_free_key($privateKey);
        
        $signToBase64 = Base64Url::encode($encode_data);
        
        $signToBase64 .= '$SHA256';
        
        $headers['Authorization'] = "YOP-RSA2048-SHA256 " . $protocolVersion . "/" . $appKey . "/" . $timestamp . "/" . $EXPIRED_SECONDS . "/" . $signedHeaders . "/" . $signToBase64;
        
        if ($YopRequest->config->debug) {
            var_dump("authString=" . $authString);
            var_dump("canonicalURI=" . $canonicalURI);
            var_dump("canonicalQueryString=" . $canonicalQueryString);
            var_dump("canonicalHeader=" . $canonicalHeader);
            var_dump("canonicalRequest=" . $canonicalRequest);
            var_dump("signToBase64=" . $signToBase64);
        }
        $YopRequest->headers = $headers;
    }
}


public function __set($name, $value){
    $this->$name = $value;
    
}
public function __get($name){
    return $this->$name;
}

public function setSignRet($signRet) {
    $signRetStr = $signRet?'true':'false';
    $this->signRet = $signRet;
    $this->addParam($this->Config->SIGN_RETURN, $signRetStr);
}

public function setSignAlg($signAlg) {
    $this->signAlg = $signAlg;
}

public function setEncrypt($encrypt) {
    $this->encrypt = $encrypt;
}

public function setVersion($version) {
    $this->version = $version;
}

public function setMethod($method) {
    $this->method = $method;
}