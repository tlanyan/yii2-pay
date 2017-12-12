<?php
/**
 * @brief Wxpay component
 *
 * @author tlanyan<tlanyan@hotmail.com>
 * @link http://tlanyan.me
 */
/* vim: set ts=4; set sw=4; set ss=4; set expandtab; */

namespace tlanyan;

use Yii;
use yii\base\Object;
use yii\httpclient\Client;
use yii\httpclient\Response;

class Wxpay extends Object
{
    public $appid;
    public $appkey;

    /**
     * @var merchant id
     */
    public $mchid;

    /**
     * @var Wxpay callback url
     */
    public $notifyUrl;

    /**
     * @var sign generate algorithm
     */
    public $signType = 'HMAC-SHA256';

    public $logCategory = 'wxpay';

    /**
     * @const the gate way to get prepay id
     */
    const ORDER_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    private function getPostData(string $orderId, int $amount, string $body, string $ip, string $tradeType, string $sceneInfo = "")
    {
	    $data = [
		    'appid' => $this->appid,
		    'body' => $body,
		    'mch_id' => $this->mchid,
		    'nonce_str' => Yii::$app->security->generateRandomString(32),
		    'notify_url' => $this->notifyUrl,
		    'out_trade_no' => $orderId,
		    'spbill_create_ip' => $ip,
		    'sign_type' => $this->signType,
		    'total_fee' => $amount,
		    'trade_type' => $tradeType,
	    ];
	    if ($sceneInfo) {
	    	$data["scene_info"] = $sceneInfo;
	    }
	    return $data;
    }

    private function makeRequest(array $data)
    {
	    $client = new Client();
	    return $client->createRequest()
		    ->setMethod('post')
		    ->setFormat(Client::FORMAT_XML)
		    ->setUrl(self::ORDER_URL)
		    ->setData($data)
		    ->send();
    }

    private function dealResponse(Response $response, bool $isApp)
    {
	    if ($response->isOk) {
	    	$response->setFormat(Client::FORMAT_XML);
		    $data = $response->data;
		    Yii::info("微信支付返回内容：", $this->logCategory);
		    Yii::info($data, $this->logCategory);
		    if ($data['return_code'] === 'SUCCESS') {
			    if ($data['result_code'] === 'SUCCESS') {
			    	if ($isApp) {
					    return [
						    'code' => 0,
						    'prepayId' => $data['prepay_id'],
					    ];
				    }
				    return [
					    'code' => 0,
					    'mwebUrl' => $data['mweb_url'],
				    ];
			    }
			    return [
				    'code' => 1,
				    'message' => $data['err_code_des'],
			    ];
		    } else {
			    Yii::error("微信支付请求出错，返回内容：", $this->logCategory);
			    Yii::error($data, $this->logCategory);
		    }
	    }

	    return [
		    'code' => 1,
		    'message' => 'fail to communicate with wxpay server',
	    ];
    }

    /**
     * get the pay parameter for the client
     * @var int $orderId
     * @var float $amount
     * @var string $body brief of trade
     * @var string $ip the client ip to pay
     * @return array
     */
    public function getPrepayId(string $orderId, int $amount, string $body, string $ip)
    {

    	$postData = $this->getPostData($orderId, $amount, $body, $ip, "APP");
        $postData['sign'] = $this->getSign($postData);

	    Yii::info("微信支付请求数据：", $this->logCategory);
	    Yii::info($postData, $this->logCategory);
        $response = $this->makeRequest($postData);

        return $this->dealResponse($response, true);
    }

	public function getWapPayUrl(string $orderId, int $amount, string $body, string $ip, string $sceneInfo)
    {
    	$postData = $this->getPostData($orderId, $amount, $body, $ip, "MWEB", $sceneInfo);
    	$postData["sign"] = $this->getSign($postData);

	    Yii::info("微信支付请求数据：", $this->logCategory);
	    Yii::info($postData, $this->logCategory);
    	$response = $this->makeRequest($postData);

    	return $this->dealResponse($response, false);
    }

    /**
     * @var array $data the array to generate sign
     * @return string
     */
    public function getSign($data)
    {
        $data = array_filter($data);
        ksort($data);
        $stringA = '';
        foreach ($data as $key => $value) {
            $stringA .= $key . '=' . $value . '&';
        }
        $stringSignTemp = $stringA . 'key=' . $this->appkey;

        if ($this->signType === 'HMAC-SHA256') {
            $sign = hash_hmac('sha256', $stringSignTemp, $this->appkey);
        } else {
            $sign = md5($stringSignTemp);
        }

        return strtoupper($sign);
    }

    /**
     * check the integrity of the callback post data
     * @var array $data the post data array
     * @return boolean
     */
    public function checkSign($data)
    {
        $sign = $data['sign'];
        unset($data['sign']);

        if ($this->getSign($data) === $sign) {
            return true;
        }

        return false;
    }

    public function init()
    {
        parent::init();

        $this->signType = strtoupper($this->signType);
        if ($this->signType !== 'HMAC-SHA256') {
            $this->signType = 'MD5';
        }
    }
}
