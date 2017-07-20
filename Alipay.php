<?php
/**
 * @brief Alipay component
 *
 * @author tlanyan<tlanyan@hotmail.com>
 * @link http://tlanyan.me
 */
/* vim: set ts=4; set sw=4; set ss=4; set expandtab; */

namespace tlanyan;

use AopClient;
use yii\base\Object;
use AlipayTradeAppPayRequest;

require_once (__DIR__ . '/sdks/alipay/AopSdk.php');

class Alipay extends Object
{
    /*
     * @var string alipay gateway url
     */
    public $gatewayUrl = 'https://openapi.alipay.com/gateway.do';

    public $appid;

    /*
     * @var string alipay public key
     * the public key should remove the header and footer, one line
     */
    public $alipayRsaPubKey;

    /*
     * @var string developer private key
     * the private key should remove the header and footer, one line
     */
    public $merchantRsaPrivateKey;

    public $charset = 'UTF-8';

    public $signType = 'RSA2';

    const PRODUCT_CODE = 'QUICK_MSECURITY_PAY';

    /*
     * @var string alipay callback url
     */
    public $notifyUrl;

    /*
     * @var string order id
     * @var string order subject
     * @var string amount
     * @var string body the description of the order, max length is 128
     * @var string order close timeout
     */
    public function getPayParameter(string $orderId, string $subject, string $amount, string $body, string $timeoutExpress = '30m')
    {
        $aop = new AopClient();

        $aop->gatewayUrl = $this->gatewayUrl;
        $aop->appId = $this->appid;
        $aop->rsaPrivateKey = $this->merchantRsaPrivateKey;
        $aop->format = $this->format;
        $aop->charset = $this->signType;
        $aop->signType = $this->signType;
        $aop->alipayrsaPublicKey = $this->alipayRsaPublicKey;

        $request = new AlipayTradeAppPayRequest();
        $bizContent = [
            'subject' => $subject,
            'out_trade_no' => $orderId,
            'body' => $body,
            'timeout_express' => $timeoutExpress,
            'total_amount' => $amount,
            'product_code' => self::PRODUCT_CODE,
        ];
        $request->setNotifyUrl($this->notifyUrl);
        $request->setBizContent(json_encode($bizContent));
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);

        return $response;
    }

    public function checkCallbackData(array $postData)
    {
        $aop = new AopClient();
        $aop->alipayrsaPublicKey = $this->alipayRsaPublicKey;

        return $aop->rsaCheckV1($postData, null, 'RSA');
    }
}
