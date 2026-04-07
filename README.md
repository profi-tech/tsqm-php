[![CI](https://github.com/profi-tech/tsqm-php/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/profi-tech/tsqm-php/actions/workflows/ci.yml)

# What is TSQM?

TSQM is a low-level PHP library for transactional and reliable execution of code involving external calls, service requests, database queries, etc. In case of an error, the code can be retried, or if retries are not feasible, it could be “compensated”.

## How low is "low-level"?

One of the main requirements for the library was its ability to integrate into any PHP codebase. TSQM requires PHP 8.2+ and provides basic classes and methods that can be embedded into almost any project or framework. The main classes are:
- **Task**: A class-wrapper for PHP code, allowing to specify retry policies, arguments and other execution options.
- **TSQM Engine**: Schedules, executes, retries, and handles errors for tasks.

:warning: **Attention!** TSQM does not work out of the box; it requires integration and configuration.

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

TSQM engine is configured via the `Tsqm\Options` object:

```php
use Tsqm\Tsqm;
use Tsqm\Options;
use Tsqm\Repository\PdoRepository;

$dsn = "<PDO dsn of database where you created a table>";
$username = "<your username>";
$password = "<your password>";
$pdo = new PDO($dsn, $username, $password);

$tsqm = new Tsqm(
  (new Options())
    ->setRepository(new PdoRepository($pdo)) // Repository for task persistence (default: InMemoryRepository)
    ->setLogger(new MyLogger()) // PSR-3 compatible logger
    ->setContainer($container) // PSR-11 DI container e.g. PHP-DI
    ->setQueue(new MyQueue()) // Queue implementation
    ->setForceSyncRuns(true) // Force synchronous runs for debugging and unit testing
    ->setMaxNestingLevel(10) // Maximum number of nested transactions
    ->setMaxGeneratorTasks(1000) // Maximum number of tasks in a generator
);
```

Available repository implementations:
- `Tsqm\Repository\PdoRepository` — persists tasks to a MySQL or SQLite database via PDO.
- `Tsqm\Repository\InMemoryRepository` — stores tasks in memory (default, useful for testing).

You can also implement `Tsqm\Repository\RepositoryInterface` for custom storage backends.


## 4. Creating a task

To create tasks, you need to create a new `Task` object and set the necessary fields:

```php
$task = (new Tsqm\Task())
  ->setCallable("greet")
  ->setArgs("John Doe");
```
The argument for `setCallable` could be:

- A callable object of a class with the `__invoke` method (recommended).
- Name of static method along with its class name e.g. `MyClass::myMethod`
- Name of global functions e.g. `MyGlobalFunction` (strongly not recommended).

:warning: If you use callable objects, you need to set a DI container for the TSQM engine that implements `Psr\Container\ContainerInterface` (PSR-11).
The callable object must be accessible in the container by its class name.

Task supports the following options:
- `setScheduledFor` — DateTimeInterface object with the scheduled execution time.
- `setWaitInterval` — Time interval to wait before starting a task (string compatible with `DateTime::modify()`).
- `setIsSecret` — if true, task args and results will be logged as secrets.
- `setTrace` — trace object to trace task execution via logs.

Also you could specify retry policy via `setRetryPolicy` and `RetryPolicy` object:
- `setMaxRetries` — Maximum number of retries.
- `setMinInterval` — Minimum interval between retries in milliseconds or a string that can be parsed by DateTime::modify().
- `setBackoffFactor` — factor to multiply the interval between retries.
- `setUseJitter` — if true, a random value will be added to the interval between retries.

Example:

```php

class Greeter {
  public function __invoke(string $name): string {
    return "Hello, $name!";
  }
}

class MyContainer implements Psr\Container\ContainerInterface {
  ...
}

$tsqm = new Tsqm\Tsqm(
  (new Tsqm\Options())
    ->setRepository(new Tsqm\Repository\PdoRepository($pdo))
    ->setContainer(new MyContainer())
);

$task = (new Tsqm\Task())
  ->setCallable(new Greeter())
  ->setArgs("John Doe")
  ->setRetryPolicy(
    (new Tsqm\RetryPolicy())
      ->setMaxRetries(3)
      ->setMinInterval(5000)
  );

...
```

## 5. Running a task

To execute a task, you need to call the `run` method:
```php
$persistedTask = $tsqm->run($task);
```

The `run` method returns a `PersistedTask` object with the execution state:
```php
echo "Task id: ".$persistedTask->getId();
if ($persistedTask->isFinished()) {
    if (!$persistedTask->hasError()) {
      $result = $persistedTask->getResult();
    } else {
      $error = $persistedTask->getError();
    }	
}
```

## 6. Retries and scheduled tasks

A task does not complete if:

- An error occurred and the task has a retry policy set.
- The task has a future execution time set via the `setScheduledFor` option.

Tasks that need to be retried can be run through the `poll` method:

```php
$tsqm->poll(
  100, // Number of tasks to poll
  30, // Time in seconds to "step back" from the current time (useful for the fallback mode)
  10 // Idle time in seconds if no tasks found
);

```
Although the `poll` method can perform scheduled runs, for production it should be used only as a fallback to the main queue-based approach.

## 7. Queues

To integrate queues in TSQM, you need to implement the `Tsqm\Queue\QueueInterface` and add the implementing class during the TSQM engine initialization:


```php
class MyQueue implements Tsqm\Queue\QueueInterface {

  public function enqueue(string $taskName, string $taskId, DateTimeInterface $scheduledFor): void {
    ... put $taskId into your favorite message broker like RabbitMQ, Apache Kafka etc.
  }

  /**
   * @param callable(string $taskId): ?Task $callback
   */
  public function listen(string $taskName, callable $callback): void {
    ... listen to your favorite message broker like RabbitMQ, Apache Kafka etc
    ... receive $taskId and call $callback with it
  }
}

$tsqm = new Tsqm\Tsqm(
  (new Tsqm\Options())
    ->setRepository(new Tsqm\Repository\PdoRepository($pdo))
    ->setQueue(new MyQueue())
);

```

The TSQM engine will automatically call the `enqueue` method of your class if the task needs to be executed later.

To receive and handle the tasks, call the `listen` method in a separate script:

```php
$tsqm->listen($taskName);
```


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

$task = $tsqm->run($task);
```

If the `purchase` task fails, the transaction execution will stop and retry according to the policy "3 attempts every 5 seconds". If all attempts fail, the `rollback` task will be executed.

:warning: **Attention!** TSQM caches the results of completed tasks and checks the determinism of the transaction during execution, meaning it is safe to retry the call as many times as needed.

## 9. Logging

TSQM logs every step of task and transaction execution. 
To access these logs, you need to provide a class that implements `Psr\Log\LoggerInterface` e.g. [Monolog](https://github.com/Seldaek/monolog)
 
 ```php

$tsqm = new Tsqm\Tsqm(
  (new Tsqm\Options())
    ->setRepository(new Tsqm\Repository\PdoRepository($pdo))
    ->setLogger(
      // PSR-3 LoggerInterface instance, e.g. Monolog
    )
);

```

# Limitations and warnings

- TSQM is generally not a Workflow Engine but a library for reliably executing PHP code with external calls. However, you can try to use the library as a workflow engine, provided that all task code is stored and executed within the same codebase.

- Task data is deleted from the database after execution, as the persistent storage is used only to ensure transactional consistency.

- For errors, the class, message, code, and trace are stored as structured data (not serialized PHP objects).

- TSQM is lightweight and fast but has not been tested under heavy loads.




