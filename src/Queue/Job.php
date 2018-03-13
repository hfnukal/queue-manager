<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * Basic Job. You can create your Job by extending this class
 * and implement run method. If inicialization is required
 * init method can be implemented.
 *
 * @author hfnukal
 */
class Job implements JobInterface {
    /**
     * @var
     */
    private $id;
    /**
     * @var string
     */
    private $data;
    /**
     * @var int
     */
    private $delay;
    /**
     * @var \DateTime
     */
    private $created;
    /**
     * @var \DateTime
     */
    private $lastupdate;
    /**
     * @var string
     */
    private $cron;
    /**
     * @var string
     */
    private $queue;
    /**
     * @var string
     */
    private $state;
    /**
     * @var string
     */
    private $classname;

    private $context;

    public function __construct($data=NULL, $prpperties=NULL) {
        if(is_array($data)) {
            $this->data=json_encode($data);
        } else {
            $this->data=$data;
        }

        if(is_array($prpperties)) {
            $this->id = $prpperties[Queue::COL_ID] ?? NULL;
            $this->delay = $prpperties[Queue::COL_DELAY] ?? 0;
            $this->created = $prpperties[Queue::COL_CREATED] ?? NULL;
            $this->lastupdate = $prpperties[Queue::COL_LAST_UPDATE] ?? NULL;
            $this->cron = $prpperties[Queue::COL_CRON] ?? NULL;
            $this->queue = $prpperties[Queue::COL_QUEUE] ?? NULL;
            $this->classname = $prpperties[Queue::COL_CLASS] ?? NULL;
            $this->state = $prpperties[Queue::COL_STATE] ?? NULL;
        }
    }

    public function getData() {
        return $this->data;
    }

    public function getClassname() {
        return $this->classname;
    }

    public function getCreated() {
        return $this->created;
    }

    public function getLastupdate() {
        return $this->lastupdate;
    }

    public function getState() {
        return $this->state;
    }

    public function getCron() {
        return $this->cron;
    }

    public function getDelay(): int {
        return empty($this->delay)?0:$this->delay;
    }

    public function getId() {
        return $this->id;
    }

    public function init($context=NULL) {
        return $this->context=$context;
    }

    public function run() {
        return $this->getData();
    }

    public function setData($data) {
        $this->data=$data;
    }

    public function setDelay(int $delay) {
        $this->delay=$delay;
    }

    public function setId($id) {
        $this->id=$id;
    }

}
