<?php
/* 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * QueueRunner offers methosds to configure and run Jobs queues.
 *
 * @author Honza Fnukal <hfnukal@honzicek.com>
 */
class QueueRunner {
    /**
     * Name of queue
     * @var string 
     */
    private $queue;
    
    /**
     * Max count of jobs running in one cycle
     * @var integer 
     */
    private $maxJobCount=5;
    
    /**
     * Max time spend by running jobs in one cycle. After reaching this time,
     * no more jobs will run in runJobs()
     * @var integer 
     */
    private $maxJobTime=60;
    
    /**
     * Use microseconds. Seconds used for delay and max times by default.
     * @var boolean 
     */
    private $useMicro=false;
    
    /**
     * Time to sleep when run in loop. By default in seconds, if $useMicro==true
     * it will use microseconds.
     * @var integer 
     */
    private $sleeptime=60;
    
    /**
     * When true, loop continues.
     * @var boolean 
     */
    private $run=true;
    
    public function __construct($queue) {
        $this->queue=$queue;
    }
    
    /**
     * Queue for runner
     * @return \Queue\Queue
     */
    public function getQueue():Queue {
        return $this->queue;
    }
    /**
     * Run cycles in loop. Sleep after each cycle.
     */
    public function loop() {
        while($this->run) {
            $this->runJobs();
            sleep($this->sleeptime);
        }
    }
    
    /**
     * Run one cycle of jobs. Cycle is defined by time limit and job count limit.
     */
    public function runJobs() {
        $counter = $this->maxJobCount;
        $time=$this->getTime();
        while( $counter>0 && ($this->getTime()-$time)>$this->maxJobTime ) {
            $this->runOneJob();
            $counter--;
        }
    }
    
    /**
     * Pop and run one job from queue.
     */
    public function runOneJob() {
        $job=$this->queue->popJob();
        $success=true;
        $data=$job->getData();
        try {
            $data=$job->run();
        } catch (Exception $ex) {
            $queue->errorJob($job);
            $success=false;
        }
        if ($success) {
            $queue->completeJob($data);
        }
    }
    
    /**
     * Enqueue cron jobs matching time. Default time is current time.
     * @param type $time
     */
    public function queueCron($time='now') {
        $jobs= $this->queue->cronJobList();
        foreach($jobs as $job) {
            $this->queue->addJob($job);
        }
    }
    
    /**
     * Setters and getters
     */
    
    public function setMaxJobCount($maxJobCount) {
        $this->maxJobCount=$maxJobCount;
    }
    public function getMaxJobCount() {
        return $this->maxJobCount;
    }
    
    public function setMaxJobTime($maxJobTime) {
        $this->maxJobTime=$maxJobTime;
    }
    public function getMaxJobTime() {
        return $this->maxJobTime;
    }
    
    public function setUseMicro($maxJobCount) {
        $this->maxJobCount=$maxJobCount;
    }
    public function getUseMicro() {
        return $this->maxJobCount;
    }
    
    public function setSleeptime($sleeptime) {
        $this->sleeptime=$sleeptime;
    }
    public function getSleeptime() {
        return $this->sleeptime;
    }
    
    public function setRun($run) {
        $this->run=$run;
    }
    public function getRun() {
        return $this->run;
    }
    
    private function getTime() {
        return useMicro?microtime():time();
    }
}
