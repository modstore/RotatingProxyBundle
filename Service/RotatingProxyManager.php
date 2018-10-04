<?php
// src/RotatingProxyBundle/Service/RotatingProxyManager.php

namespace Modstore\RotatingProxyBundle\Service;

use Campo\UserAgent;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Exception\RequestException;
use Modstore\RotatingProxyBundle\Entity\Group;
use Modstore\RotatingProxyBundle\Entity\Log;
use Modstore\RotatingProxyBundle\Exception\RotatingProxyNoProxiesAvailableException;
use Monolog\Logger;
use Modstore\RotatingProxyBundle\Entity\Proxy;
use Modstore\RotatingProxyBundle\Exception\RotatingProxyResponseUnsuccessfulException;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Config\Definition\Exception\Exception;

class RotatingProxyManager
{
    // Don't allow requests from the same proxy with less than this number of seconds between.
    const TIME_BETWEEN_REQUESTS = 45;

    protected $em;

    /**
     * @var \Monolog\Logger
     */
    protected $logger;
    
    // TODO make this configurable.
    protected $attempts = 5;

    public function __construct(EntityManager $entityManager, Logger $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    public function setAttempts($attempts) 
    {
        $this->attempts = $attempts;
    }

    /**
     * Get the next available proxy to use.
     *
     * @param string $name
     * @return \RotatingProxyBundle\Entity\Proxy|null
     * @throws RotatingProxyNoProxiesAvailableException
     */
    public function getNextProxy($name = 'default')
    {
        // Get the first active proxy ordered by oldest timestamp.
        // TODO change this to status not disabled.
        /** @var Proxy $proxy */
        $proxy = $this->em->getRepository('ModstoreRotatingProxyBundle:Proxy')->findOneEnabledByGroupNameOrderByTs($name);
        if (null === $proxy) {
            throw new RotatingProxyNoProxiesAvailableException();
        }

        // Make sure there's at least x seconds between requests from the same proxy.
        if (time() - $proxy->getTs() < self::TIME_BETWEEN_REQUESTS) {
            $sleepTime = self::TIME_BETWEEN_REQUESTS - (time() - $proxy->getTs());
            $this->logger->info('Waiting ' . $sleepTime . ' seconds.');
            sleep($sleepTime);
        }
        
        // Set the current time so we don't use this one again until the others have been used.
        $proxy->setTs(time());

        // Set the current time against this group.
        $group = $this->em->getRepository('ModstoreRotatingProxyBundle:Group')->findOneByProxyAndName($proxy, $name);
        if (null === $group) {
            $group = new Group($name);
            $proxy->addGroup($group);
        }

        $group->setTs(time());

        // If it's disabled status but long enough ts since it was disabled, set active again.
        if (Proxy::STATUS_DISABLED == $proxy->getStatus()) {
            $proxy->setStatus(Proxy::STATUS_ENABLED);
            $proxy->setCount(0);
        }
        
        $this->em->flush();

        return $proxy;
    }

    /**
     * @param \Modstore\RotatingProxyBundle\Entity\Proxy $proxy
     * @param array $options - array of client options.
     * @return \Goutte\Client
     * @throws \Exception
     */
    public function getClient(Proxy $proxy, array $options = [])
    {
        $config = [
            'proxy' => $proxy->getUri(),
            'headers' => [
                'User-Agent' => UserAgent::random(),
            ],
        ];

        // TODO use a recursive merge function.
        if (array_key_exists('headers', $options)) {
            $config['headers'] += $options['headers'];
        }

        $client = new Client();
        $client->setClient(new GuzzleClient($config));
        
        return $client;
    }

    /**
     * Try crawling the page, and if we get a successful response, return the client.
     *
     * @param $url
     *
     * @param string $name
     * @param array $options - Guzzle client options.
     * @param string $method
     * @param array $requestOptions
     * @return \Symfony\Component\DomCrawler\Crawler|null
     * @throws RotatingProxyNoProxiesAvailableException
     * @throws RotatingProxyResponseUnsuccessfulException
     */
    public function crawlPage($url, $name = 'default', array $options = [], $method = 'GET', array $requestOptions = [])
    {
        // Try up to x times.
        $e = null;
        for ($i = 0; $i < $this->attempts; $i++) {
            // Get the next available proxy.
            $proxy = $this->getNextProxy($name);

            $client = $this->getClient($proxy, $options);

            try {
                $crawler = $client->request($method, $url, $requestOptions);
            }
            catch (RequestException $e) {
                $this->logger->warning('Unable to send request through proxy: ' . $e->getMessage());
                /** @var Group $group */
                $group = $this->em->getRepository('ModstoreRotatingProxyBundle:Group')->findOneByProxyAndName($proxy, $name);
                $group->addLog(new Log($url, $i));

                continue;
            }

            /** @var Group $group */
            $group = $this->em->getRepository('ModstoreRotatingProxyBundle:Group')->findOneByProxyAndName($proxy, $name);
            $group->addLog(new Log($url, $i, $client->getResponse()->getStatus()));

            // The request was successful, but the response code isn't one that we can crawl.
            if ($client->getResponse()->getStatus() == 404) {
                throw new RotatingProxyResponseUnsuccessfulException($client->getResponse(), $client->getRequest());
            }

            // If request failed, or we get the robot check page.
            if ($client->getResponse()->getStatus() < 200 || $client->getResponse()->getStatus() > 299) {
                $this->requestFailed($group, $client->getResponse(), $client->getRequest());
            } else {
                $this->requestSuccessful($group, $client->getResponse(), $client->getRequest());

                return $crawler;
            }
        }
        
        if ($e instanceof RequestException) {
            throw $e;
        }
        
        // Request hasn't been successful after max attempts.
        throw new RotatingProxyResponseUnsuccessfulException($client->getResponse(), $client->getRequest());
    }

    /**
     * Send a request with guzzle and return the response.
     *
     * @param $url
     * @param string $name
     * @param array $options
     * @param string $method
     * @param array $requestOptions
     * @return null|ResponseInterface
     * @throws RotatingProxyNoProxiesAvailableException
     */
    public function request($url, $name = 'default', array $options = [], $method = 'GET', array $requestOptions = [])
    {
        // Try up to x times.
        $e = null;
        for ($i = 0; $i < $this->attempts; $i++) {
            // Get the next available proxy.
            $proxy = $this->getNextProxy($name);

            $config = [
                'proxy' => $proxy->getUri(),
                'headers' => [
                    'User-Agent' => UserAgent::random(),
                ],
            ];

            // TODO use a recursive merge function.
            if (array_key_exists('headers', $options)) {
                $config['headers'] += $options['headers'];
            }

            $client = new GuzzleClient($config);

            try {
                $response = $client->request($method, $url, $requestOptions);
            }
            catch (RequestException $e) {
                $this->logger->warning('Unable to send request through proxy: ' . $e->getMessage());
                /** @var Group $group */
                $group = $this->em->getRepository('ModstoreRotatingProxyBundle:Group')->findOneByProxyAndName($proxy, $name);
                $group->addLog(new Log($url, $i));

                continue;
            }

            /** @var Group $group */
            $group = $this->em->getRepository('ModstoreRotatingProxyBundle:Group')->findOneByProxyAndName($proxy, $name);
            $group->addLog(new Log($url, $i, $client->getResponse()->getStatus()));

            return $response;
        }

        if ($e instanceof RequestException) {
            throw $e;
        }

        // Request hasn't been successful after max attempts.
        return null;
    }

    /**
     * Mark a request failed using this proxy. After 3 times it will be disabled.
     *
     * @param Group $group
     * @param Response $response
     * @param Request $request
     */
    public function requestFailed(Group $group, Response $response, Request $request)
    {
        $this->logger->error('Rotating proxy failed: ' . $group->getProxy()->getId() . ' ' . $group->getName(), [
            'content' => $response->getContent(),
            'uri' => $request->getUri(),
            'status' => $response->getStatus(),
        ]);

        $group->setFailCount($group->getFailCount() + 1);
        
        $this->em->flush();
    }

    /**
     * Request was successful.
     *
     * @param Group $group
     * @param Response $response
     * @param Request $request
     */
    public function requestSuccessful(Group $group, Response $response, Request $request)
    {
        $this->logger->info('Rotating proxy successful: ' . $group->getProxy()->getId() . ' ' . $group->getName(), [
            'uri' => $request->getUri(),
            'status' => $response->getStatus(),
        ]);

        $group->setSuccessCount($group->getSuccessCount() + 1);

        $this->em->flush();
    }
}