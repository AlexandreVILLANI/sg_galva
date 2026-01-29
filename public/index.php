<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $trustedProxies = $context['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? $_SERVER['TRUSTED_PROXIES'] ?? false;
    
    if ($trustedProxies) {
        Request::setTrustedProxies(
            explode(',', $trustedProxies),
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT
        );
    }

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};