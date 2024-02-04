<?php

namespace WechatPay\GuzzleMiddleware;

class NoopValidator implements Validator
{
	public function validate(\Psr\Http\Message\ResponseInterface $response)
	{
		return true;
	}
}