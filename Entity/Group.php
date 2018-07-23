<?php

namespace Modstore\RotatingProxyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Proxy
 *
 * @ORM\Table(name="modstore_rotating_proxy_group", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="modstore_rotating_proxy_group_name_idx", columns={"proxy_id", "name"})
 * }, indexes={
 *     @ORM\Index(name="modstore_rotating_proxy_group_proxy_name_idx", columns={"proxy_id", "name"})
 * })
 * @ORM\Entity(repositoryClass="Modstore\RotatingProxyBundle\Repository\GroupRepository")
 */
class Group
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\Modstore\RotatingProxyBundle\Entity\Proxy")
     * @ORM\JoinColumn(name="proxy_id", referencedColumnName="id", nullable=false)
     */
    private $proxy;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="success_count", type="integer", options={"default" = 0})
     */
    private $successCount;

    /**
     * @var int
     *
     * @ORM\Column(name="fail_count", type="integer", options={"default" = 0})
     */
    private $failCount;

    /**
     * @var int
     *
     * @ORM\Column(name="ts", type="integer", options={"default" = 0})
     */
    private $ts;

    /**
     * @var Log[]
     *
     * @ORM\OneToMany(targetEntity="Log", mappedBy="group", cascade={"persist"})
     */
    private $logs;

    public function __construct($name)
    {
        $this->name = $name;
        $this->successCount = 0;
        $this->failCount = 0;
        $this->ts = 0;
        $this->logs = new ArrayCollection();
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
     * @return mixed
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param mixed $proxy
     * @return Group
     */
    public function setProxy(Proxy $proxy)
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Group
     */
    public function setName(string $name): Group
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * @param int $successCount
     * @return Group
     */
    public function setSuccessCount(int $successCount): Group
    {
        $this->successCount = $successCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getFailCount(): int
    {
        return $this->failCount;
    }

    /**
     * @param int $failCount
     * @return Group
     */
    public function setFailCount(int $failCount): Group
    {
        $this->failCount = $failCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getTs(): int
    {
        return $this->ts;
    }

    /**
     * @param int $ts
     * @return Group
     */
    public function setTs(int $ts): Group
    {
        $this->ts = $ts;

        return $this;
    }

    /**
     * @param Log $log
     * @return Group
     */
    public function addLog(Log $log)
    {
        $log->setGroup($this);
        $this->logs->add($log);

        return $this;
    }
}

