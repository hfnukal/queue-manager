<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * Job interface for Queue
 * You can implement this interface for you custom job.
 * Better way is to extend Job class.
 *
 * @author hfnukal
 */
interface JobInterface {
    /**
     * @return string id of the job
     */
    public function getId();

    /**
     * Set id of the job
     */
    public function setId($id);

    /**
     * Get data for the job
     * @return mixed
     */
    public function getData();

    /**
     * Set data for the job
     */
    public function setData($data);

    /**
     * If job should run with delay
     * @return int Delay in ms
     */
    public function getDelay();

    /**
     * Set delay for job
     * @param int $delay
     */
    public function setDelay(int $delay);

    /**
     * Init job with context
     */
    public function init($context=NULL);

    /**
     * Perform job
     */
    public function run();
}
