<?php

namespace Modstore\RotatingProxyBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Modstore\RotatingProxyBundle\Entity\Proxy;

class LoadProxyData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $proxy = new Proxy();
        $proxy->setHost('111.111.111.111');
        $proxy->setPort(11111);
        $proxy->setTs(time() - 100);
        $proxy->setStatus(Proxy::STATUS_ENABLED);
        $proxy->setCount(1);

        $manager->persist($proxy);
        $manager->flush();
    }
}