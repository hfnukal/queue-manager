<?php
/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Queue;

use \Queue\QueueInterface;
use Exception;

/**
 * Queue implementation using Doctrine database connection.
 *
 * @author Honza Fnukal <hfnukal@honzicek.com>
 */
class DoctrineQueue implements Queue {
    use QueueCronTrait;
    
    /**
     *
     * @var string Name of queue
     */
    private $queuename;
    
    /**
     *
     * @var mixed Context for jobs
     */
    private $context;
    
    /**
     *
     * @var type Doctrine connection
     */
    private $connection;
    
    /**
     *
     * @var string Name of table in database
     */
    private $tablename = 'queue';
    
    // State flags
    const STATE_QUEUE='q';
    const STATE_ERROR='e';
    const STATE_HISTORY='h';
    const STATE_ACTIVE='a';
    const STATE_TRASH='t';
    const STATE_CRON='c';
        
    /**
     * 
     * @param type $name Queue name
     * @param type $connection Doctrine Connection
     */
    public function __construct($context, $connection, $name=NULL) {
        $this->queuename=$name;
        $this->context=$context;
        $this->connection=$connection;
    }
    
    /**
     * Initialize database
     * @param type $database
     */
    public function createSchema($database='queuedb') {
        $sm= $this->connection->getSchemaManager();
        //$sm->createDatabase($database);
        $schema=$sm->createSchema();
        $queueTable=$schema->createTable($this->tablename);
        $queueTable->addColumn($this::COL_ID,'integer',array('columnDefinition'=>'INTEGER PRIMARY KEY AUTOINCREMENT'));
        $queueTable->addColumn($this::COL_QUEUE,'string',array('length'=>64));
        $queueTable->addColumn($this::COL_DATA,'string',array('length'=>255,'notnull'=>false));
        $queueTable->addColumn($this::COL_STATE,'string',array('length'=>1));
        $queueTable->addColumn($this::COL_DELAY,'integer',array('default'=>0));
        $queueTable->addColumn($this::COL_CREATED,'datetime',array('columnDefinition'=>'DATETIME DEFAULT CURRENT_TIMESTAMP'));
        $queueTable->addColumn($this::COL_LAST_UPDATE,'datetime',array('columnDefinition'=>'DATETIME DEFAULT CURRENT_TIMESTAMP'));
        $queueTable->addColumn($this::COL_CLASS,'string',array('notnull'=>true));
        $queueTable->addColumn($this::COL_CRON,'string',array('length'=>10,'notnull'=>false));
        
        $platform = new \Doctrine\DBAL\Platforms\SqlitePlatform();
        foreach($schema->toSql($platform) as $sql) {
            $this->connection->executeUpdate($sql);
        }
    }
    
    public function getQueueList() {
        $sql = "SELECT DISTINCT ".$this::COL_QUEUE." AS ".$this::COL_QUEUE." FROM ".$this->tablename;
        $queues= $this->connection->fetchAll($sql);
        $res=array();
        foreach ($queues as $queue) {
            array_push($res, $queue['queue']);
        }
        return $res;
    }
    
    /**
     * {@inheritdoc}
     */
    public function addJob(Job $job, $state=self::STATE_QUEUE): Job {
        $class=get_class($job);
        $this->connection->insert($this->tablename, array(
            $this::COL_QUEUE => $this->getName(),
            $this::COL_CLASS => $class,
            $this::COL_DATA => $job->getData(),
            $this::COL_DELAY => $job->getDelay(),
            $this::COL_STATE => $state
        ));
        $job->setId($this->connection->lastInsertId());
        return $job;
    }
        
    /**
     * {@inheritdoc}
     */
    public function addCronJob($job, $cron) {
        $class=get_class($job);
        $this->connection->insert($this->tablename, array(
            $this::COL_QUEUE => $this->getName(),
            $this::COL_CLASS => $class,
            $this::COL_DATA => $job->getData(),
            $this::COL_DELAY => $job->getDelay(),
            $this::COL_STATE => $this::STATE_CRON,
            $this::COL_CRON => $cron
        ));
        $job->setId($this->connection->lastInsertId());
        return $job;        
    }
        
    /**
     * {@inheritdoc}
     */
    public function eraceJob(Job $job) {
        $this->connection->delete($this->tablename, 
                array($this::COL_ID => $job->getId(), $this::COL_QUEUE=>$this->getName())
        );
        return $job;
    }
    
    /**
     * {@inheritdoc}
     */
    public function errorJob(Job $job) {
        return $this->setJobState($job, $this::STATE_ERROR);
    } 
    
    /**
     * {@inheritdoc}
     */
    public function getActiveJob() {
        return $this->getJobWhere(
                $this::COL_STATE." = ?", 
                array($this::STATE_ACTIVE)
        );
    }
    
    /**
     * Return Job by where condition and parameters as array
     * @param string $where
     * @param Array $params
     * @return Array Array of Job instances
     */
    private function getJobWhere($where, $params) {
        
        $sql = "SELECT * FROM {$this->tablename} WHERE ".$this::COL_QUEUE." = ?"
                . " AND ".$where." ORDER BY ".self::COL_LAST_UPDATE;
        
        $arr = $this->connection->fetchAssoc($sql, array_merge( array($this->getName()), $params ) );
        if (!is_array($arr)) {
            return NULL;
        }
        $job = $this->getJobInstance($arr);
        return $job;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName(): string {
        return $this->queuename;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getQueueSize(): int {
        $sql = "SELECT COUNT(id) AS count FROM {$this->tablename} "
        . "WHERE ".$this::COL_QUEUE." = ? AND ".$this::COL_STATE." = ?";
        return $this->connection->fetchColumn( $sql, array($this->getName(), $this::STATE_QUEUE), 0 );
    } 
    
    /**
     * {@inheritdoc}
     */
    public function isJobRunning(): bool {
        return $this->getActiveJob()!=NULL;
    }
    
    /**
     * {@inheritdoc}
     */
    public function popJob() {
        // check if there is no active job
        $activeJob = $this->getActiveJob();
        if ($activeJob != NULL) {
            throw new \Exception("There is active job already");
        }
        // change state of job to active
        $popJob = $this->getJobWhere($this::COL_STATE." = ?", array($this::STATE_QUEUE));
        if($popJob!=NULL) {
            $popJob = $this->setJobState($popJob, $this::STATE_ACTIVE);
        }
        // return active job
        return $popJob;
    }
    
    /**
     * {@inheritdoc}
     */
    public function completeJob($data) {
        $activeJob = $this->getActiveJob();
        if($activeJob!=NULL) {
            $now=new \DateTime();
            $this->connection->update($this->tablename,
                    array( 
                        $this::COL_STATE=>$this::STATE_HISTORY, 
                        $this::COL_DATA=>$data,
                        $this::COL_LAST_UPDATE=> $now->format('Y-m-d H:i:s.u') //TODO Update on db side
                    ), 
                    array( $this::COL_ID=>$activeJob->getId(), $this::COL_QUEUE=>$this->getName() )
            );
            $activeJob->setData($data);
        } else {
            throw new Exception("No active job");
        }
        return $activeJob;
    }

    /**
     * Get job by ID.
     */
    public function getJob($id):Job {
//        $sql = "SELECT * FROM {$this->tablename} WHERE ".$this::COL_QUEUE." = ? AND ".$this::COL_ID." = ?";
//        $arr = $this->connection->fetchAssoc( $sql, array($this->getName(), $id) );
//        $job = $this->getJobInstance($arr);
        return $this->getJobWhere(
                $this::COL_ID." = ?", 
                array($id)
                );
    }
    
    /**
     * Returns Job instace with class and properties stored in $arr
     * @param type $arr
     * @return \Queue\class
     */
    public function getJobInstance($arr) {
        $class = $arr[$this::COL_CLASS];
        if(!class_exists($class)) {
            throw new Exception("Class $class is not valid.");
        }
        $job = new $class( 
            $arr[$this::COL_DATA] ?? NULL, 
            $arr
        );
        return $job;
    }
    
    /**
     * Help function to set job state in db
     * @param Job $job
     * @param string $state
     */
    private function setJobState($job,$state) {
        if($job!=NULL) {
            $now=new \DateTime();
            $this->connection->update($this->tablename,
                    array(
                        $this::COL_STATE=>$state, 
                        $this::COL_LAST_UPDATE=> $now->format('Y-m-d H:i:s.u') //TODO Update on dn side
                    ),
                    array( $this::COL_ID=>$job->getId(), $this::COL_QUEUE=>$this->getName() )
            );
        }
        return $job;
    }
    
    /**
     * {@inheritdoc}
     */
    public function queueJob(Job $job) {
        $this->setJobState($job, $this::STATE_QUEUE);
        return $job;
    }
    
    /**
     * {@inheritdoc}
     */
    public function trashJob(Job $job) {
        if($job->getState()==self::STATE_TRASH) {
            $this->eraceJob($job);
            $job->setId("deleted");
        } else {
            $this->setJobState($job, $this::STATE_TRASH);
        }
        return $job;
    }
    
    //TODO paging
    /**
     * Helper method for Job lists
     * 
     * @param type $state
     * @return array
     */
    private function getList($state) {
        $statement = $this->connection->executeQuery(
                "SELECT * FROM {$this->tablename} WHERE ".$this::COL_QUEUE." = ? AND state = ?"
                        . " ORDER BY ".self::COL_LAST_UPDATE, 
                array($this->getName(), $state )
        );
        $res = $statement->fetchAll();
        $jobs = array();
        foreach($res as $j) {
            $job = $this->getJobInstance($j);
            array_push($jobs, $job);
        }
        //return $res;
        return $jobs;
    }

    /**
     * {@inheritdoc}
     */    
    public function jobList(): Array {
        return $this->getList($this::STATE_QUEUE);
    } 
    
    /**
     * {@inheritdoc}
     */
    public function errorJobList(): Array {
        return $this->getList($this::STATE_ERROR);
    } 
    
    /**
     * {@inheritdoc}
     */
    public function historyJobList(): Array {
        return $this->getList($this::STATE_HISTORY);
    } 
    
    /**
     * {@inheritdoc}
     */
    public function trashJobList(): Array {
        return $this->getList($this::STATE_TRASH);
    }
    
    /**
     * {@inheritdoc}
     */
    public function cronJobList(): Array {
        return $this->getList($this::STATE_CRON);
    }    
}
