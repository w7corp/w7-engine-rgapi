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

namespace W7\Sdk\Module\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ApiException extends Exception
{
    /** @var ResponseInterface */
    protected $response;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, ?ResponseInterface $response = null)
    {
        if (!is_null($response)) {
            $response->getBody()->rewind();
            $this->response = $response;
        }
        parent::__construct($message, $code, $previous);
    }
    
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
