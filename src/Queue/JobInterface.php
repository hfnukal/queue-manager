<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * Job interface for Queue
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
     * @retutn mixed
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
     * Perform job
     */
    public function run();
}
