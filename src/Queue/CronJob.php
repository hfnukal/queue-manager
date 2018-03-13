<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

/**
 * Description of CronJob
 *
 * @author hfnukal
 */
class CronJob extends Job {
    public function __construct($data = NULL, $delay = 0, $prpperties = NULL) {
        parent::__construct($data, $delay, $prpperties);
    }
}
