<?php

namespace Modstore\RotatingProxyBundle\Exception;

class RotatingProxyNoProxiesAvailableException extends RotatingProxyException
{
    public function __construct()
    {
        parent::__construct('No proxies available');
    }
}