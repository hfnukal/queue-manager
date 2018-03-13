<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * Description of Queue
 *
 * @author hfnukal
 */
interface Queue {

    const COL_ID='id';
    const COL_QUEUE='queue';
    const COL_CLASS='classname';
    const COL_DATA='data';
    const COL_STATE='state';
    const COL_DELAY='delay';
    const COL_CREATED='created';
    const COL_LAST_UPDATE='lastupdate';
    const COL_CRON='cron';

    /**
     * @return string Queue name
     */
    public function getName();

    /**
     * @return Array of active queue names
     */
    public function getQueueList();
    
    /**
     * Add Job to queue
     * @param \Queue\Job $newJob
     * @return Job with updated ID
     */
    public function addJob(Job $newJob);
        
    /**
     * Adds cron job. Cron job runs on time. Time is defined by $cron pattern.
     * @param type $job
     * @param type $cron
     */
    public function addCronJob($job, $cron);
    
    /**
     * Move job back to queue
     * @param \Queue\Job $job
     */
    public function queueJob(Job $job);
    
    /**
     * Move job to trash from queue
     * @param \Queue\Job $job
     */
    public function trashJob(Job $job);
    
    /**
     * Move job to error state from queue
     * @param \Queue\Job $job
     */
    public function errorJob(Job $job);
    
    /**
     * Erace job
     * @param \Queue\Job $job
     */
    public function eraceJob(Job $job);
        
    /**
     * Start next job in queue. 
     * Job will be removed from queue and can be retrieved by getActiveJob()
     * When finish, it will be moved to history, on error to error
     * @return Job Job that will run
     */
    public function popJob();

    /**
     * Get job by id
     * @param string $id
     */
    public function getJob($id):Job;
    
    /**
     * Complete job, update data and move from active to history
     * @param mixed $data
     */
    public function completeJob($data);
    
    /**
     * Return true if there is active job running
     * @return boolean
     */
    public function isJobRunning();
    
    /**
     * @return Job Return active running job
     */
    public function getActiveJob();
 
    /**
     * @return Array Array of jobs in history
     */
    public function jobList();
 
    /**
     * @return Array Array of jobs in history
     */
    public function historyJobList();
    
    /**
     * @return Array Array of jobs in trash
     */
    public function trashJobList();
    
    /**
     * @return Array Array of jobs in error state
     */
    public function errorJobList();

    /**
     * @return Array Array of jobs in cron
     */
    public function cronJobList();

    /**
     * @return int Actual queue size
     */
    public function getQueueSize();
    
}