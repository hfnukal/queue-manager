<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * Description of QueueManager
 *
 * @author hfnukal
 */
class QueueManager {
    
    const DEFAULT_QUEUE='_default';
    private $queueClass="Queue\\DoctrineQueue";
    private $conn;
    private $context;
    private $queues=array();
    private $runners=array();
    
    public function __construct(\Doctrine\DBAL\Connection $conn, $context, $queueClass=NULL) {
        $this->conn=$conn;
        $this->context=$context;
        if ($this->queueClass == NULL) {
            $this->queueClass = $queueClass;
        }
    }
    
    /**
     * $return Array of active queue names
     */
    public function getQueueList() {
        return $this->getQueue()->getQueueList();
    }
    
    /**
     * $return Job instance of job from data
     */
    public function instanceJob($data) {
        return $this->getQueue()->getJobInstance($data);
    }
        
    /**
     * Get runner for queue by name
     * @param string $name Name of Queue
     * @return \Queue\QueueRunner
     */
    public function getRunner($name=NULL):QueueRunner {
        if ($name === NULL) {
            $name = $this::DEFAULT_QUEUE;
        }
        if(!isset($this->queues[$name])) {
            $this->runners[$name] = new QueueRunner( $this->context, $this->getQueue($name) );
        }
        return $this->runners[$name];
    }
    
    /**
     * Get Queue by name with class
     * @param string $name
     * @return \Queue\Queue
     */
    public function getQueue($name=NULL):Queue {
        if ($name === NULL) {
            $name = $this::DEFAULT_QUEUE;
        }
        if(!isset($this->queues[$name])) {
            $class=$this->getQueueClass();
            $this->queues[$name] = new $class(
                    $this->context,
                    $this->conn, 
                    $name
            );
        }
        return $this->queues[$name];
    }
    
    /**
     * Return name of class for Queue
     * @return string
     */
    public function getQueueClass():string {
        return $this->queueClass;
    }
    
    /**
     * Set name of class for Queue
     * @param string $queueClass
     */
    public function setQueueClass($queueClass) {
        $this->queueClass=$queueClass;
    }
    
    public function install() {
        $this->getQueue()->createSchema();
    }
}
