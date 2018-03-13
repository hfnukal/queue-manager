<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Events;

/**
 * Description of CacheRecord
 *
 * @author hfnukal
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ApiResource
 * @ApiFilter(SearchFilter::class, properties={"id": "exact", "queue": "exact", "state": "exact"})
 * @ApiFilter(DateFilter::class, properties={"created","updated"})
 * @ApiFilter(OrderFilter::class, properties={"queue","created","updated","state"})
 */
class Queue {

    public function __construct() {
        //$this->lines = new ArrayCollection(); // Initialize $lines as an Doctrine collection
    }

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * Queue name
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    public $queue;

    /**
     * Job Class
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    public $classname;

    /**
     * Cron string "* * * * *"
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $cron;

    /**
     * Job data as string. Preferably Json encoded
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $data;

    /**
     * State [q|e|h|a|t|c] queue, error, history, active, trash, cron
     * @var int
     * @ORM\Column(type="string", nullable=false)
     */
    public $state;

    /**
     * Delay for job
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    public $delay;

    /**
     * Create datetime
     * @var \DateTime
     * @ORM\Column(type="datetime",nullable=false, columnDefinition="DATETIME DEFAULT CURRENT_TIMESTAMP")
     */
    public $created;

    /**
     * Update datetime
     * @var \DateTime updated
     * @ORM\Column(type="datetime",nullable=false, columnDefinition="DATETIME DEFAULT CURRENT_TIMESTAMP")
     */
    public $lastupdate;

    /**
     * @PrePersist
     */
    public function updatedOnPrePersist()
    {
        $now =  new \DateTime();
        $this->created = $now;
        $this->lastupdate = $now;
    }

    /**
     * @PreUpdate
     */
    public function updatedOnPreUpdate()
    {
        $this->lastupdate = new \DateTime();
    }

    /**
     * Get the value of Id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of Id
     *
     * @param mixed id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the value of Queue name
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Set the value of Queue name
     *
     * @param string queue
     *
     * @return self
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Get the value of Job Class
     *
     * @return string
     */
    public function getClassname()
    {
        return $this->classname;
    }

    /**
     * Set the value of Job Class
     *
     * @param string classname
     *
     * @return self
     */
    public function setClassname($classname)
    {
        $this->classname = $classname;
        return $this;
    }

    /**
     * Get the value of Cron string "* * * * *"
     *
     * @return string
     */
    public function getCron()
    {
        return $this->cron;
    }

    /**
     * Set the value of Cron string "* * * * *"
     *
     * @param string cron
     *
     * @return self
     */
    public function setCron($cron)
    {
        $this->cron = $cron;
        return $this;
    }

    /**
     * Get the value of Job data as string. Preferably Json encoded
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the value of Job data as string. Preferably Json encoded
     *
     * @param string data
     *
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get the value of State [q|e|h|a|t|c] queue, error, history, active, trash, cron
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the value of State [q|e|h|a|t|c] queue, error, history, active, trash, cron
     *
     * @param int state
     *
     * @return self
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Get the value of Delay for job
     *
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Set the value of Delay for job
     *
     * @param int delay
     *
     * @return self
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * Get the value of Create datetime
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get the value of Update datetime
     *
     * @return \DateTime updated
     */
    public function getLastupdate()
    {
        return $this->lastupdate;
    }

    /**
     * Set the value of Update datetime
     *
     * @param \DateTime updated lastupdate
     *
     * @return self
     */
    public function setLastupdate(\DateTime $lastupdate)
    {
        $this->lastupdate = $lastupdate;
        return $this;
    }

}
