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

namespace W7\Sdk\Module\Middlewares;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;

class AddSignMiddleware
{
    public function __invoke(string $app_id, string $app_secret, string $link_app_id, string $account_type): \Closure
    {
        return function (callable $handler) use ($app_id, $app_secret, $link_app_id, $account_type) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $app_id, $app_secret, $link_app_id, $account_type) {
                $timeStamp  = time();
                $nonce      = $this->random(16);
                $bodyString = $request->getBody()->getContents();
                $body       = json_decode($bodyString, true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    $request->getBody()->rewind();
                    parse_str($request->getBody()->getContents(), $body);
                }

                if ('POST' === $request->getMethod()) {
                    $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
                }

                $data = [
                    'appid'       => $app_id,
                    'link_app_id' => $link_app_id,
                    'timestamp'   => $timeStamp,
                    'type'        => $account_type,
                    'nonce'       => $nonce,
                ];

                if (!empty($body)) {
                    $data['body'] = $body;
                }

                $data['sign'] = $this->getSign($data, $app_secret);

                $bodyStream = Utils::streamFor(http_build_query($data));
                $request    = $request->withBody($bodyStream);

                return $handler($request, $options);
            };
        };
    }

    protected function getSign($data, string $app_secret = ''): string
    {
        unset($data['sign']);

        ksort($data, SORT_STRING);

        return md5(http_build_query($data, '', '&') . $app_secret);
    }

    protected function random(int $length = 16): string
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            /** @noinspection PhpUnhandledExceptionInspection */
            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }
}
