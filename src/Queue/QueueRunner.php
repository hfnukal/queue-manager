<?php
/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

use Exception;
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
     * Context for Jobs
     * @var string
     */
    private $context;

    /**
     * Max count of jobs running in one cycle
     * @var integer
     */
    private $maxJobCount=3;

    /**
     * Max time spend by running jobs in one cycle. After reaching this time,
     * no more jobs will run in runJobs()
     * @var integer
     */
    private $maxJobTime=30;

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

    /**
     *
     * @param mixed $context Context for Jobs
     * @param string $queue Queue name
     */
    public function __construct($context, $queue) {
        $this->context=$context;
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
        $ret=array();
        $ret[]= array("starttime" => $time, 'maxjobs' => $counter, 'maxtime' => $this->maxJobTime);
        while( $counter>0 && ($this->getTime()-$time)<$this->maxJobTime ) {
            try {
                $job=$this->runOneJob();
                if($job==NULL) {
                    $ret[]= array( 'done' => "No more jobs." );
                    break;
                }
                $ret[]=$job;
            } catch (Exception $exc) {
                $ret[]= array( 'error' => $exc->getMessage() );
            }
            $counter--;
        }
        $ret[]=array("duration" => ($this->getTime()-$time) );
        return $ret;
    }

    /**
     * Pop and run one job from queue.
     */
    public function runOneJob() {
        $job=$this->queue->popJob();
        $success=true;
        if(empty($job)) {
            return NULL;
        }
        $data=$job->getData();
        try {
            error_reporting(E_ALL);
            $job->init($this->context);
            $data=$job->run();
        } catch (\Exception $ex) {
            //$job=$this->queue->errorJob($job);
            $d=$job->getData();
            if(is_array($d)) {
                $d['exception'] = $ex.': '.$ex->getMessage();
            } else {
                $d=$d.'\nException: '.$ex.': '.$ex->getMessage();
            }
            $job->setData($d);
            $success=false;
        } finally {
            if ($success) {
                $job=$this->queue->completeJob($data);
            } else {
                $job=$this->queue->errorJob($job);
            }
        }
        return array( "status" => ($success?'ok':'error'),
          "job" => [
            "id" => $job->getId(),
            "classname" => $job->getClassname(),
            "data" => $job->getData()
          ]
        );
    }

    /**
     * Enqueue cron jobs matching time. Default time is current time.
     * @param type $time
     */
    public function queueCron($time='now') {
        $jobs= $this->queue->dueJobList();
        $ret=array();
        foreach($jobs as $job) {
            $ret[]=$this->queue->addJob($job);
        }
        return $ret;
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
        return $this->useMicro?microtime():time();
    }
}
