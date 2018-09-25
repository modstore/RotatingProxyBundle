<?php

namespace Modstore\RotatingProxyBundle\Exception;

use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

class RotatingProxyResponseUnsuccessfulException extends RotatingProxyException
{
    protected $response;

    protected $request;

    public function __construct(Response $response, Request $request)
    {
        parent::__construct('Response : ' . $response->getStatus() . ' ' . $request->getUri());
        $this->response = $response;
        $this->request = $request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
