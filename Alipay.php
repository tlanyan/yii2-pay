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
    public $appid;

    /**
     * @var string alipay public key
     * the public key should remove the header and footer, one line
     */
    public $alipayRsaPublicKey;

    /**
     * @var string developer private key
     * the private key should remove the header and footer, one line
     */
    public $merchantRsaPrivateKey;

    /**
     * @var string developer private key file path
     */
    public $merchantRsaPrivateKeyFile = null;

    /**
     * @var string alipay callback url
     */
    public $notifyUrl;

    /**
     * @var string alipay gateway url
     */
    public $gatewayUrl = 'https://openapi.alipay.com/gateway.do';


    /**
     * @var string response format
     */
    public $format = 'json';

    public $charset = 'UTF-8';

    public $signType = 'RSA2';

    const PRODUCT_CODE = 'QUICK_MSECURITY_PAY';

    /**
     * get pay parameter for the client
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

    /**
     * check the sign of callback data
     * @var array $data the post data array
     * @return boolean
     */
    public function checkSign(array $data)
    {
        $aop = new AopClient();
        $aop->alipayrsaPublicKey = $this->alipayRsaPublicKey;

        return $aop->rsaCheckV1($data, null, $this->signType);
    }

    public function init()
    {
        parent::init();

        if ($this->merchantRsaPrivateKeyFile !== null) {
            if (!is_file($this->merchantRsaPrivateKeyFile)) {
                throw new RsaKeyFileNotFoundException('商户密钥文件：' . $this->merchantRsaPrivateKeyFile . ' 不存在');
            }

            $fd = fopen($this->merchantRsaPrivateKeyFile, 'r');
            $key = '';
            while (!feof($fd)) {
                $line = trim(fgets($fd, 4096));
                if (!$line || substr($line, 0, 4) === '----') {
                    continue;
                }

                $key .= $line;
            }

            $this->merchantRsaPrivateKey = $key;
            fclose($fd);
        }

        if (!$this->merchantRsaPrivateKey) {
            throw new RsaKeyNotSetException('商户密钥未配置！');
        }
    }
}
