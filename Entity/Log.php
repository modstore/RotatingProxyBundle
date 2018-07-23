<?php

namespace Modstore\RotatingProxyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductLog
 *
 * @ORM\Table(name="modstore_rotating_proxy_log")
 * @ORM\Entity(repositoryClass="Modstore\RotatingProxyBundle\Repository\LogRepository")
 *
 */
class Log
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
     * @ORM\ManyToOne(targetEntity="Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", nullable=false)
     */
    private $group;

    /**
     * @var int
     *
     * @ORM\Column(name="code", type="integer")
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="attempt", type="integer")
     */
    private $attempt;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    public function __construct($url, int $attempt = 0, int $code = null)
    {
        $this->url = $url;
        $this->code = $code;
        $this->attempt = $attempt;
        $this->created = new \DateTime('now');
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
     * @param Group $group
     * @return Log
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @return int
     */
    public function getCode(): ?int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }
}

