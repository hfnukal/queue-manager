<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * Description of Job
 *
 * @author hfnukal
 */
class Job implements JobInterface {
    var $id;
    var $data;
    var $delay;
    var $created;
    var $lastupdate;
    var $cron;
    var $queue;
    
    public function __construct($data=NULL, $delay=0, $prpperties=NULL) {
        $this->data=$data;
        $this->delay=$delay;
        if(is_array($prpperties)) {
            $this->id=$prpperties[Queue::COL_ID];
            $this->created=$prpperties[Queue::COL_CREATED];
            $this->lastupdate=$prpperties[Queue::COL_LAST_UPDATE];
            $this->cron=$prpperties[Queue::COL_CRON];
            $this->queue=$prpperties[Queue::COL_QUEUE];
            $this->className=$prpperties[Queue::COL_CLASS]; //TODO
        }
    }
    
    public function getData() {
        return $this->data;
    }

    public function getCreated(): int {
        return $this->created;
    }

    public function getCron(): string {
        return $this->cron;
    }

    public function getDelay(): int {
        return $this->delay;
    }

    public function getId(): string {
        return $this->id;
    }

    public function run() {
        
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
