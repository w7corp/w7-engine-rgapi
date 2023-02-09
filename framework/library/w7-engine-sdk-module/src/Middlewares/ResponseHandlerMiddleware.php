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
use Psr\Http\Message\ResponseInterface;
use W7\Sdk\Module\Exceptions\ApiException;
use W7\Sdk\Module\Exceptions\ApiHttpException;
use W7\Sdk\Module\Support\ApiResponse;

class ResponseHandlerMiddleware
{
    public function __invoke(): \Closure
    {
        return function (callable $handler) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler) {
                $promise = $handler($request, $options);
                return $promise->then(
                    function ($response) {
                        return $this->responseHandler($response);
                    }
                );
            };
        };
    }

    protected function responseHandler(ResponseInterface $response): ApiResponse
    {
        if (200 !== $response->getStatusCode()) {
            throw new ApiHttpException('访问接口失败', -1, null, $response);
        }

        $data = $response->getBody()->getContents();

        $data = json_decode($data, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ApiHttpException('数据解析失败', -2, null, $response);
        }

        if (($data['code'] ?? -1) != 0) {
            throw new ApiException($data['message'] ?? 'API错误', -3, null, $response);
        }

        $body = $response->getBody();
        $body->rewind();
        $response = new ApiResponse($response->getStatusCode(), $response->getHeaders(), $body, $response->getProtocolVersion(), $response->getReasonPhrase());
        if (is_array($data['data'])) {
            $response->withData($data['data']);
        } elseif (is_string($data['data'])) {
            $dataJson = json_decode($data['data'], true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                $response->withData(['data' => $data['data']]);
            } else {
                $response->withData($dataJson);
            }
        }

        return $response;
    }
}
