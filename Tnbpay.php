<?php
/**
 * @brief
 *
 * @author tlanyan<tlanyan@hotmail.com>
 * @link https://tlanyan.me
 */

/* vim: set ts=4; set sw=4; set ss=4; set expandtab; */

namespace tlanyan;

use Yii;
use yii\base\Object;
use yii\httpclient\Client;

class Tnbpay extends Object
{
	public $version = "2.0.0";

	const CHARSET = "UTF-8";

	const SIGN_TYPE = "MD5";

	public $appid;

	public $mch_id;

	public $appSecret;

	public $notifyUrl;

	public $logCategory = "tnbpay";

	const URL = "https://api.tnbpay.com/pay/gateway";

	public function getPrepayId(string $orderId, int $amount, string $body)
	{
		$data = [
			"method" => "mbupay.wxpay.jswap2",
			"version" => $this->version,
			"charset" => self::CHARSET,
			"sign_type" => self::SIGN_TYPE,
			"appid" => $this->appid,
			"mch_id" => $this->mch_id,
			"nonce_str" => Yii::$app->security->generateRandomString(32),
			"body" => $body,
			"out_trade_no" => $orderId,
			"total_fee" => $amount,
			"notify_url" => $this->notifyUrl,
		];

		$data["sign"] = $this->getSign($data);

		$client = new Client();
		$response = $client->createRequest()
			->setFormat(Client::FORMAT_XML)
			->setUrl(self::URL)
			->setMethod("post")
			->setData($data)
			->setOptions([
				"sslVerifyPeer" => false,
			])
			->send();
		if ($response->isOk) {
			$res = $response->data;
			Yii::info($res, $this->logCategory);
			if ($res["return_code"] === "SUCCESS") {
				if ($res["result_code"] === "SUCCESS") {
					return [
						"code" => 0,
						"prepayId" => $res["prepay_id"],
					];
				} else {
					return [
						"code" => 1,
						"message" => $res["err_code"],
					];
				}
			}

			return [
				"code" => 1,
				"message" => $res["return_msg"],
			];
		}

		return [
			"code" => 1,
			"message" => "服务器无响应",
		];
	}

	public function getSign(array $data)
	{
		ksort($data);
		$data = array_filter($data, function($value) {
			return $value !== "" && $value !== null;
		});
		$string1 = http_build_query($data);
		$string1 .= "&key={$this->appSecret}";
		$string1 = urldecode($string1);

		return strtoupper(md5($string1));
	}

	public function checkSign(array $data)
	{
		$sign = $data["sign"];
		unset($data["sign"]);

		return $this->getSign($data) === $sign;
	}
}