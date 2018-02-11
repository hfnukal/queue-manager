<?php
/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * Description of QueueCronTrait
 *
 * @author Honza Fnukal <hfnukal@honzicek.com>
 */
trait QueueCronTrait {
    public function dueJobList($time = 'now') {
        $data = $this->cronJobList();

        //$currentDate = date('Y-m-d H:i');
        //$currentTime = strtotime($currentDate);

        $jobs = array();

        if (is_array($data)) {
            foreach ($data as $job) {
                try {
                    if (\Cron\CronExpression::factory( $job->getCron() )->isDue($time) ) {
                        $jobs[] = $job;
                    }
                } catch (Exception $ex) {
                    $this->errorJob($job, $ex->getMeasage());
                }
            }
        }
        return $jobs;
    }
    
    abstract public function cronJobList(): Array;
}
