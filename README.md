[![CI](https://github.com/profi-tech/tsqm-php/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/profi-tech/tsqm-php/actions/workflows/ci.yml)

# What is TSQM?

TSQM is a low-level PHP library for transactional and reliable execution of code involving external calls, service requests, database queries, etc. In case of an error, the code can be retried, or if retries are not feasible, it could be “compensated”.

## How low is "low-level"?

One of the main requirements for the library was its ability to integrate into any PHP codebase starting from version 7.4. TSQM provides basic classes and methods that can be embedded into almost any project or framework. The main classes are:
- **Task**: A class-wrapper for PHP code, allowing the specification of retry policies and other execution parameters.
- **TSQM Engine**: Schedules, executes, retries, and handles errors for tasks.

:warning: **Attention!** TSQM does not work out of the box; it requires a process of integration and configuration.

# Basic usage

## 1. Install

```
composer require profi-tech/tsqm-php
```

## 2. Database Initialization

TSQM requires a table in a MySQL or SQLite database. You can get the SQL to create this table using:

```bash
vendor/bin/tsqm-db
```

Or by specifying the vendor and table name:

```bash
vendor/bin/tsqm-db mysql my_tsqm_table
vendor/bin/tsqm-db sqlite my_tsqm_table
```

Run the generated SQL on the database you want TSQM to work with.

## 3. Configuring TSQM engine

```php
$dsn = "<PDO dsn of database where you created a table>";
$username = "<your username>";
$password = "<your password>";
$pdo = new PDO($dsn, $username, $password);

$tsqm = new Tsqm\Tsqm($pdo);
...
```


## 4. Creating a task

To create tasks, you need to create a new `Task` object and set the necessary fields:

```php
$task = (new Tsqm\Task())
  ->setCallable("greet")
  ->setArgs("John Doe")
  ->setRetryPolicy(
    (new Tsqm\RetryPolicyI())
      ->setMaxRetries(10)
      ->setMinInterval(1000)
  )
```
The argument for `setCallable` could be:

- A callable object of a class with the `__invoke` method (recommended).
- Name of static method along with its class name e.g. `MyClass::myMethod`
- Name of global functions e.g. `MyGlobalFunction` (highly not recommended). 

:warning: If you use callable objects, you need to set a DI container for the TSQM engine that implements `Tsqm\Container\ContainerInterface`. The callable object must be accessible in the container by its class name.


```php
class MyContainer implements Tsqm\Container\ContainerInterface {
  ...
}

$tsqm = new Tsqm\Tsqm(
  $pdo,
  (new Tsqm\Options())
    ->setContainer(new MyContainer())
);
...
```

## 5. Running a task

To execute a task, you need to call the `runTask` method:
```php
$task = $tsqm->runTask($task);
```

The execution result will be available in the `$task` object:
```php
echo "Task id: ".$task->getId();
if ($task->isFinished()) {
    if (!$task->hasError()) {
      $result = $task->getResult();
    } else {
      $error = $task->getError();
    }	
}
```

## 6. Retries

A task does not complete if:

- An error occurred and the task has a retry policy set.
- The task has a future execution time set via the `setScheduleTime` option.

Tasks that need to be retried can be obtained through the `getScheduledTasks` method:

```php
$tasks = $tsqm->getScheduledTasks();
foreach ($tasks as $task) {
  $task = $tsqm->runTask($task);
}

```

Or retrieve a task by its ID if you are using queues:

```php
$taskId = "<scheduled task id from queue>";
$task = $tsqm->getTask($taskId);
if ($task) {
  $task = $tsqm->runTask($task);
  ...
}
```

## 7. Queues

Unfinished tasks can be polled using `getScheduledTasks` and executed in a separate script, but it is better to use queues.

To integrate queues in TSQM, you need to implement the `Tsqm\Queue\QueueInterface` and add the implementing class during the TSQM engine initialization:


```php
class MyQueue implements Tsqm\Queue\QueueInterface {
  public function enqueue(string $taskId, DateTime $scheduledFor): void {
    ... put taskId to your favorite message broker like RabbitMQ, Apache Kafka etc.
  }
}

$tsqm = new Tsqm\Tsqm(
  $pdo,
  (new Tsqm\Options())
    ->setQueue(new MyQueue())
);

```

Then, in a separate daemon script, retrieve messages from your queue and execute them:

```php
while (true) {
  ....
  $task = $tsqm->getTask($taskId);
  if ($task) {
    $tsqm->runTask($task);	
  }
}

```

The TSQM engine will automatically call the `enqueue` method of your class if the task needs to be executed later.

## 8. Transactions

In addition to simple tasks, TSQM supports transactions; you can implement a task where the callable returns a generator of tasks. All standard logics will apply, such as error handling, retries, etc. An example of implementing a transaction:

```php
class Greet
{
  ...
  public function __invoke(string $name): Generator
  {
    $valid = yield (new Task())
      ->setCallable($this->validateName)
      ->setArgs($name);
    if (!$valid) {
      return false;
    }

    $greeting = yield (new Task())
      ->setCallable($this->createGreeting)
      ->setArgs($name);

    try {
      $greeting = yield (new Task())
        ->setCallable($this->purchase)
        ->setArgs($greeting)
        ->setIsSecret(true)
        ->setRetryPolicy(
          (new RetryPolicy())
            ->setMaxRetries(3)
            ->setMinInterval(5000)
      );
    } catch (Exception $e) {
      yield (new Task())
        ->setCallable($this->revertGreeting)
        ->setArgs($greeting);
      return false;
    }

    return $greeting;
  }
}
...
$task = (new Task())
  ->setCallable(new Greet(...))
  ->setArgs("John Doe");

$task = $tsqm->runTask($task);
```

If the `purchase` task fails, the transaction execution will stop and retry according to the policy "3 attempts every 5 seconds". If all attempts fail, the `rollback` task will be executed.

:warning: **Attention!** TSQM caches the results of completed tasks and checks the determinism of the transaction during execution, meaning it is safe to retry the call as many times as needed.

## 9. Logging

TSQM logs every step of task and transaction execution. To access these logs, you need to connect a class that implements the `Tsqm\Logger\LoggerInterface` interface:
 
 ```php
class MyLogger implements Tsqm\Logger\LoggerInterface {
    ...
    public function log($level, string $message, array $context = []): void {
      // Your log implementation here
    }
}

$tsqm = new Tsqm\Tsqm(
  $pdo,
  (new Tsqm\Options())
    ->setLogger(new MyLogger())
);

```

# Limitations and Warnings

- TSQM is generally not a Workflow Engine but a library for reliably executing PHP code with external calls. However, you can try to use the library as a workflow engine, provided that all task code is stored and executed within the same codebase.

- Task data is deleted from the database after execution, as the persistent storage is used only to ensure transactionality.

- For errors, only the error class, message, and code are serialized into the database.

- TSQM is lightweight but has not been tested under heavy loads.

- TSQM uses its own interfaces for container, queue, logger, etc., to avoid dependencies on external libraries, which have mostly moved to PHP 8. TSQM needs to support both PHP 7.4 and 8.





