<?php

namespace Modstore\RotatingProxyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Proxy
 *
 * @ORM\Table(name="modstore_rotating_proxy")
 * @ORM\Entity(repositoryClass="Modstore\RotatingProxyBundle\Repository\ProxyRepository")
 */
class Proxy
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const STATUS_FAILED_ONCE = 2;
    const STATUS_FAILED_TWICE = 3;
    
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="host", type="string", length=16)
     */
    private $host;

    /**
     * @var int
     *
     * @ORM\Column(name="port", type="integer")
     */
    private $port;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"default" = 1})
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="count", type="integer", options={"default" = 0})
     */
    private $count;

    /**
     * @var int
     *
     * @ORM\Column(name="ts", type="integer", options={"default" = 0})
     */
    private $ts;

    /**
     * @var Group[]
     *
     * @ORM\OneToMany(targetEntity="Group", mappedBy="proxy", cascade={"persist"})
     */
    private $groups;

    public function __construct() 
    {
        $this->status = self::STATUS_ENABLED;
        $this->count = 0;
        $this->ts = 0;
        $this->groups = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set host
     *
     * @param string $host
     *
     * @return Proxy
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set port
     *
     * @param integer $port
     *
     * @return Proxy
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Proxy
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set ts
     *
     * @param integer $ts
     *
     * @return Proxy
     */
    public function setTs($ts)
    {
        $this->ts = $ts;

        return $this;
    }

    /**
     * Get ts
     *
     * @return int
     */
    public function getTs()
    {
        return $this->ts;
    }

    /**
     * The number of successful requests.
     * 
     * @param $count
     */
    public function setCount($count) 
    {
        $this->count = $count;
    }

    public function getCount() 
    {
        return $this->count;
    }
    
    public function successfulRequest()
    {
        $this->setCount($this->getCount() + 1);
    }

    /**
     * Return the combined host and port uri.
     * 
     * @return string
     */
    public function getUri()
    {
        return $this->getHost() . ':' . $this->getPort();
    }

    /**
     * @param Group $group
     * @return Proxy
     */
    public function addGroup(Group $group)
    {
        $group->setProxy($this);
        $this->groups->add($group);

        return $this;
    }
}

