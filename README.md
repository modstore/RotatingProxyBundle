RotatingProxyBundle
===================

This bundle rotates through your pool of http proxies when sending subsequent requests.

Installation
------------
composer require modstore/rotating-proxy-bundle:dev-master

### Add RotatingProxyBundle to your application kernel

```php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Modstore\RotatingProxyBundle\ModstoreRotatingProxyBundle(),
        // ...
    );
}
```

## Add your proxy pool

Add your proxy ip addresses to the db table: modstore_rotating_proxy
- host: The proxy IP address
- port: The proxy port
- status: 1 to enable, 0 to disable  

## Usage example
```php
/** @var \Symfony\Component\DomCrawler\Crawler $crawler */
$crawler = $container->get('modstore_rotating_proxy.manager')->crawlPage(
    'https://github.com/modstore/RotatingProxyBundle',
    'github',
    ['Referer' => 'https://www.google.com']
);
```

## Notes
In order to ensure requests aren't too similar and blocked as a bot, 
each request will set a random user agent string. It is also a good idea
to provide a "Referer" string that would be a plausible organic referrer
to the page you're requesting.

The second argument is a group name. Requests are rotated within a group.
Generally for all requests to a particular domain, you would set the same
group name.

## Log
A log of all requests is stored in the modstore_rotating_proxy_log table.