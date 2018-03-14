# queue-manager
Queue management in PHP. Purpose of this library is to have basic queue management
for common hosting. Cron job, that opens webpage in some interval is what will be enough. 
Is it possible to set max time limit for running jobs as many
hosting services has limited execution time.
Main goal was to run tasks in background that needs to run slow.

Useful when you do not have possibility to instal any queue management

Instalation
-----------
Add to project using composer

```bash
composer require hfnukal/queue-manager
```

Usage
-----

Create custom job. Just extend \Queue\Job and override run method. Run method returns transformed data.
```php
class LongJob extends \Queue\Job {
    public function run() {
        sleep(10);
        return $this->getData();
    }
    
}
```

### Add Job to queue
When creating instance of QueeuManager, pass context and queue name.
Context can be empty or array or some manager.
```php
$queue='MyQueue';
$context=array();
$newJob = new LongJob()
$queuemanager = new QueueManager($context, $queue);
$queuemanager->getQueue($name)->addJob($newJob);
```

If you using some kind of framework, e.g. Symfony, you can use dependency injection to get instance of QueueManager.

### To execute jobs in queue. Usecase is run this every minute.
```php
$queuemanager->getRunner($name)->runJobs();
```

### If you want to run in loop.
```php
$queuemanager->getRunner($name)->loop();
```

### You can set parametters to runner
**$maxJobCount** - how many jobs will run in one loop
**$maxJobTime** - how long can run one loop cycle. This is useful if you running loop by calling weburl with cron job and page execution has execution time limit.
**$sleeptime** - sleep time when run in loop

```php
$runner = $queuemanager->getRunner($name);
$runner->maxJobCount=1;
```

### Using context to inject connection. 
```php
class MyAppJob extends \Queue\Job {
    public $connection;

    public function init($context=NULL) {
        $this->connecton=$context->get(\My\Connection::class);
    }
    
}
```

## Browsing queues
Queue interface provides methosds to get list of queued Jobs
