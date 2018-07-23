<?php

namespace Modstore\RotatingProxyBundle\Tests\Service;

use Goutte\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Modstore\RotatingProxyBundle\DataFixtures\ORM\LoadProxyData;
use Modstore\RotatingProxyBundle\Entity\Group;
use Monolog\Logger;
use Modstore\RotatingProxyBundle\Entity\Proxy;
use Modstore\RotatingProxyBundle\Service\RotatingProxyManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client as GuzzleClient;

class RotatingProxyManagerTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $container;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        self::bootKernel();

        $this->container = static::$kernel->getContainer();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Empty the database for the test.
        $this->em->getConnection()->query('SET FOREIGN_KEY_CHECKS=0');
        $this->loadFixtures([]);
        $this->em->getConnection()->query('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testGetNextProxy()
    {
        $proxy = new Proxy();
        $proxy->setHost('111.111.111.111');
        $proxy->setPort(11111);
        $proxy->setTs(time() - 90);
        $this->em->persist($proxy);

        $proxy2 = new Proxy();
        $proxy2->setHost('222.222.222.222');
        $proxy2->setPort(22222);
        $proxy2->setTs(time() - 100);
        $this->em->persist($proxy2);
        
        $this->em->flush();

        $rotatingProxyManager = new RotatingProxyManager($this->em, new Logger('log'));
        $nextProxy = $rotatingProxyManager->getNextProxy();

        // Confirm that the proxy we get had the earliest ts.
        $this->assertEquals($proxy2->getId(), $nextProxy->getId());
        // Confirm that we now get the other proxy.
        $nextProxy2 = $rotatingProxyManager->getNextProxy();
        $this->assertEquals($proxy->getId(), $nextProxy2->getId());
    }

    public function nextProxyDataProvider()
    {
        return array(
            array(3600, Proxy::STATUS_ENABLED, true),
            array(3600, Proxy::STATUS_DISABLED, false),
            array(3600, Proxy::STATUS_FAILED_TWICE, false),
            array(3600 * 7, Proxy::STATUS_FAILED_TWICE, true),
            array(3600 * 7, Proxy::STATUS_ENABLED, true),
        );
    }

    /**
     * @dataProvider nextProxyDataProvider
     *
     * @param $secondsAgo
     * @param $status
     * @param $expectResult
     */
    public function testGetNextProxyStatus($secondsAgo, $status, $expectResult)
    {
        $this->markTestIncomplete('Expect exception when no proxies available');
        
        $proxy = new Proxy();
        $proxy->setHost('111.111.111.111');
        $proxy->setPort(11111);
        $proxy->setTs(time() - $secondsAgo);
        $proxy->setStatus($status);
        $this->em->persist($proxy);

        $this->em->flush();

        $rotatingProxyManager = new RotatingProxyManager($this->em, new Logger('log'));
        $nextProxy = $rotatingProxyManager->getNextProxy();

        if ($expectResult) {
            $this->assertNotNull($nextProxy);
        } else {
            $this->assertNull($nextProxy);
        }
    }

    public function testRequest()
    {
        $this->markTestIncomplete('Just for testing a real request.');

        $client = new GuzzleClient();
        $response = $client->request('GET', 'http://whatismyip.org', ['proxy' => '192.199.249.45:21327']);
        $body = $response->getBody()->getContents();

        $proxy = new Proxy();
        $proxy->setHost('111.111.111.111');
        $proxy->setPort(11111);
        $proxy->setTs(time() - 50);
        $proxy->setStatus(1);
        $this->em->persist($proxy);

        $this->em->flush();

        $rotatingProxyManager = new RotatingProxyManager($this->em, new Logger('log'));

        $crawler = $rotatingProxyManager->crawlPage('http://www.modifiedstreetcars.com');
    }
    
    public function testSuccessfulRequest()
    {
        $this->loadFixtures([
            LoadProxyData::class,
        ]);

        // Create a stub.
        $rotatingProxyManager = $this->getMockBuilder(RotatingProxyManager::class)
            ->setConstructorArgs(array($this->em, new Logger('log')))
            // Set the methods to be replaced, the others will remain unchanged.
            ->setMethods(array('getClient'))
            ->getMock();

        // Create a mock and queue a response.
        $mock = new MockHandler([
            new Response(200, [], 'test'),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient(['handler' => $handler]);

        $client = new Client();
        $client->setClient($guzzleClient);

        // Configure the stub.
        $rotatingProxyManager->method('getClient')
            ->willReturn($client);

        // Don't retry for the test.
        $rotatingProxyManager->setAttempts(1);

        $crawler = $rotatingProxyManager->crawlPage('http://testing.com/test/url');

        $proxy = $this->em->getRepository('ModstoreRotatingProxyBundle:Proxy')->findOneBy([
            'host' => '111.111.111.111',
            'port' => 11111,
        ]);

        /** @var Group $group */
        $group = $this->em->getRepository('ModstoreRotatingProxyBundle:Group')->findOneByProxyAndName($proxy, 'default');

        $this->assertEquals(1, $group->getSuccessCount());
    }

    /**
     * @expectedException Modstore\RotatingProxyBundle\Exception\RotatingProxyResponseUnsuccessfulException
     */
    public function testFailedRequest()
    {
        $this->loadFixtures(array(
            LoadProxyData::class,
        ));

        // Create a stub.
        $rotatingProxyManager = $this->getMockBuilder(RotatingProxyManager::class)
            ->setConstructorArgs([$this->em, new Logger('log')])
            // Set the methods to be replaced, the others will remain unchanged.
            ->setMethods(['getClient'])
            ->getMock();

        // Create a mock and queue a response.
        $mock = new MockHandler([
            new Response(400, [], 'test'),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient(['handler' => $handler]);

        $client = new Client();
        $client->setClient($guzzleClient);

        // Configure the stub.
        $rotatingProxyManager->method('getClient')
            ->willReturn($client);

        // Don't retry for the test.
        $rotatingProxyManager->setAttempts(1);

        $crawler = $rotatingProxyManager->crawlPage('http://testing.com/test/url');

        $proxy = $this->em->getRepository('ModstoreRotatingProxyBundle:Proxy')->findOneBy([
            'host' => '111.111.111.111',
            'port' => 11111,
        ]);

        /** @var Group $group */
        $group = $this->em->getRepository('ModstoreRotatingProxyBundle:Group')->findOneByProxyAndName($proxy, 'default');

        $this->assertEquals(1, $group->getFailCount());
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->em->close();
    }
}
