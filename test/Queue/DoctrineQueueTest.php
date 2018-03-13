<?php
/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Queue;

use \PHPUnit\Framework\TestCase;
use \Queue\DoctrineQueue;

/**
 * DoctrineQueue test suite.
 *
 * @author Honza Fnukal <hfnukal@honzicek.com>
 */
class DoctrineQueueTest extends TestCase {

    /**
     * @var DoctrineQueue
     */
    protected $object;

    /**
     * Sets up the fixture, for create Sqlite database and queues.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        echo("setUp: start\n");
        $this->queue1='queue1';
        $this->queue2='queue2';
        $config = new \Doctrine\DBAL\Configuration();
        $params = array('url'=>'sqlite://test.db');
        $connection = \Doctrine\DBAL\DriverManager::getConnection($params,$config);
        $this->object1 = new DoctrineQueue([], $connection, $this->queue1);
        $this->object1->createSchema("test.db");
        $this->object = NULL;
        $this->object2 = new DoctrineQueue([], $connection, $this->queue2);
        $this->connection=$connection;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        echo("tearDown: start\n");
        $this->connection->close();
    }

    /**
     * @covers Queue\DoctrineQueue::addJob
     * @covers Queue\DoctrineQueue::isJobRunning
     * @covers Queue\DoctrineQueue::getQueueList
     * @covers Queue\DoctrineQueue::getQueueSize
     * @covers Queue\DoctrineQueue::popJob
     * @covers Queue\DoctrineQueue::getActiveJob
     * @covers Queue\DoctrineQueue::completeJob
     * @covers Queue\DoctrineQueue::historyJobList
     * @covers Queue\DoctrineQueue::eraceJob
     * @covers Queue\DoctrineQueue::jobList
     * @covers Queue\DoctrineQueue::errorJobList
     * @covers Queue\DoctrineQueue::historyJobList
     * @covers Queue\DoctrineQueue::trashJobList
     *
     * testFullForkflow.
     */
    public function testFullForkflow() {
        echo("testFullForkflow: start\n");
        $this->object=$this->object1;
        echo("testFullForkflow: object1 -----------------------\\n");
        $this->scenarioQueue();

       // Queue list 1
        echo("testFullForkflow: Queue list 1\n");
        $queueList1=$this->object->getQueueList();
        $this->assertTrue(is_array($queueList1));
        $this->assertEquals( 1, count($queueList1));
        $this->assertEquals($this->queue1, $queueList1[0]);

        echo("testFullForkflow: object2 -----------------------\\n");
        $this->object=$this->object2;
        $this->scenarioQueue();

        // Queue list 2
        echo("testFullForkflow: Queue list 2\n");
        $queueList2=$this->object->getQueueList();
        $this->assertTrue(is_array($queueList2));
        $this->assertEquals(2, count($queueList2));
        $this->assertEquals($this->queue1, $queueList1[0]);
        $this->assertEquals($this->queue2, $queueList2[1]);

    }


    public function scenarioQueue() {
        echo("scenarioQueue: start\n");
        // test data
        $data='{"test":"data"}';
        $data2='{"test":"updated data"}';
        $dataError='{"test":"error data"}';
        $dataFifo1='{"testfifo":"first"}';
        $dataFifo2='{"testfifo":"second"}';
        $delay=10;
        $job = new Job($data, $delay);
        $jobError = new Job($dataError, $delay);
        $jobFifo1 = new Job($dataFifo1);
        $jobFifo2 = new Job($dataFifo2);

        // add job to queue
        echo("scenarioQueue: add job to queue\n");
        $addJob=$this->object->addJob($job);
        $this->assertEquals($job->getData(), $addJob->getData());
        $this->assertEquals(false, $this->object->isJobRunning());
        $queueSize = $this->object->getQueueSize();
        $this->assertEquals(1, $queueSize);

        // pop job
        echo("scenarioQueue: pop job\n");
        $workJob = $this->object->popJob();
        $this->assertEquals(true, $this->object->isJobRunning());
        $this->assertEquals($job->getData(), $workJob->getData());
        $this->assertEquals($job->getDelay(), $workJob->getDelay());
        $this->assertEquals(get_class($job), get_class($workJob));

        // active job
        echo("scenarioQueue: active job\n");
        $activeJob = $this->object->getActiveJob();
        $this->assertEquals($job->getData(), $activeJob->getData());
        $this->assertEquals($job->getDelay(), $activeJob->getDelay());
        $this->assertEquals(get_class($job), get_class($activeJob));

        //T O D O: breaks the test, put in separate test
        // popJob before completeJob throws Exception
       echo("scenarioQueue: popJob before completeJob throws Exception\n");
       try {
           //$this->expectException(\Exception::class);
           $this->object->popJob();
       } catch (\Exception $ex) {
           echo("   exception message: ".$ex." >".$ex->getMessage());
           //$this->assertEquals();
       }

        // complete job
        echo("scenarioQueue: complete job\n");
        $this->object->completeJob($data2);
        $this->assertNull($this->object->getActiveJob());

        // check history
        echo("scenarioQueue: check history\n");
        $history = $this->object->historyJobList();
        $this->assertEquals(1, count($history));
        $historyJob = $history[0];
        $this->assertEquals($data2, $historyJob->getData());

        // erace job
        echo("scenarioQueue: erace job\n");
        $this->object->eraceJob($historyJob);
        $history2 = $this->object->historyJobList();
        $this->assertEquals(0, count($history2));

        // add another job
        echo("scenarioQueue: add another job\n");
        $jobErrorInQueue = $this->object->addJob($jobError);
        $this->assertEquals(1, $this->object->getQueueSize());
        $this->assertEquals(1, count($this->object->jobList()));
        $this->assertEquals(0, count($this->object->errorJobList()));
        $this->assertEquals(0, count($this->object->historyJobList()));
        $this->assertEquals(0, count($this->object->trashJobList()));

        echo("scenarioQueue: to error\n");
        $this->object->errorJob($jobErrorInQueue);
        $this->assertEquals(0, $this->object->getQueueSize());
        $this->assertEquals(0, count($this->object->jobList()));
        $this->assertEquals(1, count($this->object->errorJobList()));
        $this->assertEquals(0, count($this->object->historyJobList()));
        $this->assertEquals(0, count($this->object->trashJobList()));

        echo("scenarioQueue: to queue\n");
        $this->object->queueJob($jobErrorInQueue);
        $this->assertEquals(1, $this->object->getQueueSize());
        $this->assertEquals(1, count($this->object->jobList()));
        $this->assertEquals(0, count($this->object->errorJobList()));
        $this->assertEquals(0, count($this->object->historyJobList()));
        $this->assertEquals(0, count($this->object->trashJobList()));

        echo("scenarioQueue: to trash\n");
        $this->object->trashJob($jobErrorInQueue);
        $this->assertEquals(0, $this->object->getQueueSize());
        $this->assertEquals(0, count($this->object->jobList()));
        $this->assertEquals(0, count($this->object->errorJobList()));
        $this->assertEquals(0, count($this->object->historyJobList()));
        $this->assertEquals(1, count($this->object->trashJobList()));

        // FIFO tests
        echo("scenarioQueue: FIFO\n");
        $this->object->addJob($jobFifo1);
        $this->object->addJob($jobFifo2);

        $jobFifo1ret = $this->object->popJob();
        $this->assertEquals($jobFifo1->getData(), $jobFifo1ret->getData());
        $this->object->completeJob($jobFifo1ret->getData());

        $jobFifo2ret = $this->object->popJob();
        $this->assertEquals($jobFifo1->getData(), $jobFifo1ret->getData());
        $this->object->completeJob($jobFifo2ret->getData());

        // Test Job class
        echo("scenarioQueue: Test Job class\n");
        $testjob = new TestJob();
        $this->object->addJob($testjob);
        $testJobret = $this->object->popJob();
        $this->assertTrue($testJobret instanceof TestJob);
        $this->object->completeJob(NULL);

        // Scheduler
        echo("scenarioQueue: Scheduler\n");
        $this->assertEquals(0, count($this->object->cronJobList()));
        $this->object->addCronJob($job, "* * * * *");
        $this->assertEquals(1, count($this->object->cronJobList()));
        $this->assertEquals(1, count($this->object->dueJobList()));

        $this->object->addCronJob($job, "1 1 1 1 1");
        $this->assertEquals(2, count($this->object->cronJobList()));
        $this->assertEquals(1, count($this->object->dueJobList()));
    }
}

// Class for class test
class TestJob extends Job {

}
