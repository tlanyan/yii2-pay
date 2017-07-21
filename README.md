# yii2-pay

Yii2 pay component.

## Install

Composer:

    composer require 'tlanyan/yii2-pay:*'

or add the following line to the `require` section of your composer.json:

    "tlanyan/yii2-pay": "*"

## Channels

This library support Wxpay and Alipay.

### Alipay

The official SDK files is included in the repo, so you don't need to download the files from the Alipay website.

#### Usage

Append the following codes to the `components` array in the config file:

    'components' => [
        // other components,
        'alipay' => [
            'class' => 'tlanyan\Alipay',
                'appid' => 'your appid',
                'merchantRsaPrivateKeyFile' => 'file path to your private key',
                'alipayRsaPublicKey' => 'the alipay public rsa key',
                'notifyUrl' => 'alipay call back url',
        ],
    ],

#### Options

Alipay component accept these parameters:

- appid: **required**, the assigned appid by Alipay.
- alipayRsaPublicKey: **required**, the rsa public key from Alipay.
- merchantRsaPrivateKeyFile: **prefferd**, the merchant rsa private key file path.
- merchantRsaPrivateKey: the merchant rsa private key. If `merchantRsaPrivateKeyFile` is provided, the content is read from the file. **If you want to set private key manually, remember to remove the header,footer and break line symbols.**
- format: optional, Alipay response format. Valid values are: 'json'.
- notifyUrl: optional, Alipay call back URL.
- signType: optional, valid values are: 'RSA', 'RSA2'. Default value is 'RSA2'.

