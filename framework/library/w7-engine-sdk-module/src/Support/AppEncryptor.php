<?php

/**
 * WeEngine System
 *
 * (c) We7Team 2022 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace W7\Sdk\Module\Support;

use W7\Sdk\Module\Exceptions\ApiException;

class AppEncryptor
{
    /** @var string */
    protected $appId;

    /** @var string */
    protected $token;

    /** @var string */
    protected $aesKey;

    public function __construct(string $app_id, string $token, string $aes_key)
    {
        $this->appId  = $app_id;
        $this->token  = $token;
        $this->aesKey = $aes_key;
    }

    protected function createSignature(...$attributes): string
    {
        sort($attributes, SORT_STRING);

        return sha1(implode($attributes));
    }

    public function decrypt(string $xml): array
    {
        $xmlData = Xml::parse($xml);
        if (empty($xmlData)) {
            throw new ApiException('无效的数据。');
        }
        $xmlData = array_intersect_key($xmlData, array_flip(['Encrypt', 'MsgSignature', 'Nonce', 'TimeStamp']));
        if (4 !== count($xmlData)) {
            throw new ApiException('缺失必要的参数。');
        }
        $data = $this->decryptXml($xmlData['Encrypt'], $xmlData['MsgSignature'], $xmlData['Nonce'], $xmlData['TimeStamp']);
        $data = json_decode($data, true);
        if (JSON_ERROR_NONE != json_last_error()) {
            throw new ApiException('无效的数据。');
        }
        return $data;
    }

    /**
     * @param string     $ciphertext
     * @param string     $msgSignature
     * @param string     $nonce
     * @param int|string $timestamp
     *
     * @return string
     *
     * @throws ApiException
     */
    protected function decryptXml(string $ciphertext, string $msgSignature, string $nonce, $timestamp): string
    {
        $signature = $this->createSignature($this->token, $timestamp, $nonce, $ciphertext);

        if ($signature !== $msgSignature) {
            throw new ApiException('无效的签名。');
        }

        $plaintext = Pkcs7::unpadding(
            openssl_decrypt(
                base64_decode($ciphertext, true) ?: '',
                'aes-256-cbc',
                $this->aesKey,
                OPENSSL_NO_PADDING,
                substr($this->aesKey, 0, 16)
            ) ?: '',
            32
        );
        $plaintext     = substr($plaintext, 16);
        $contentLength = (unpack('N', substr($plaintext, 0, 4)) ?: [])[1];

        if (trim(substr($plaintext, $contentLength + 4)) !== $this->appId) {
            throw new ApiException('无效的Appid。');
        }

        return substr($plaintext, 4, $contentLength);
    }
}
