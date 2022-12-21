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

use Psr\Http\Message\RequestInterface;

class AddSignMiddleware
{
    public function __invoke(string $app_id, string $app_secret, string $account_type): \Closure
    {
        return function (callable $handler) use ($app_id, $app_secret, $account_type) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $app_id, $app_secret, $account_type) {
                $body = $request->getBody()->getContents();
                if (str_contains($request->getHeader('Content-Type')[0] ?? '', 'multipart/form-data;')) {
                    if (preg_match_all("/Content-Disposition: form-data; name=\"body\"\r\nContent-Length: (\d+)/", $body, $result)) {
                        $body = substr(
                            $body,
                            stripos($body, $result[0][0]) + strlen($result[0][0]) + 4,
                            $result[1][0]
                        );
                    }
                }
                $timeStamp = time();
                $nonce     = $this->random(8);
                $data      = [
                    'Body'      => $body,
                    'AppSecret' => $app_secret,
                    'TimeStamp' => $timeStamp,
                    'Nonce'     => $nonce,
                    'Uri'       => $request->getUri()->getPath()
                ];
                sort($data, SORT_STRING);
                $sign = sha1(implode($data));
                $uri  = $request->getUri()->withQuery(http_build_query([
                    'sign'  => $sign,
                    'appid' => $app_id,
                    'type'  => $account_type,
                    'time'  => $timeStamp,
                    'nonce' => $nonce
                ]));
                $request = $request->withUri($uri);

                return $handler($request, $options);
            };
        };
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
