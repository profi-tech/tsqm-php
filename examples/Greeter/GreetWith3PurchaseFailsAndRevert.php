<?php

namespace Examples\Greeter;

use Exception;
use Generator;
use Tsqm\RetryPolicy;
use Tsqm\Task;

class GreetWith3PurchaseFailsAndRevert
{
    private ValidateName $validateName;
    private CreateGreeting $createGreeting;
    private PurchaseWith3Fails $purchaseWith3Fails;
    private RevertGreeting $revertGreeting;
    private SendGreeting $sendGreeting;

    public function __construct(
        ValidateName $validateName,
        CreateGreeting $createGreeting,
        PurchaseWith3Fails $purchaseWith3Fails,
        RevertGreeting $revertGreeting,
        SendGreeting $sendGreeting
    ) {
        $this->validateName = $validateName;
        $this->createGreeting = $createGreeting;
        $this->purchaseWith3Fails = $purchaseWith3Fails;
        $this->revertGreeting = $revertGreeting;
        $this->sendGreeting = $sendGreeting;
    }

    public function __invoke(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        try {
            $greeting = yield (new Task())
                ->setCallable($this->purchaseWith3Fails)
                ->setArgs($greeting)
                ->setRetryPolicy((new RetryPolicy())->setMaxRetries(2)->setMinInterval(0));
        } catch (Exception $e) {
            return yield (new Task())->setCallable($this->revertGreeting)->setArgs($greeting);
        }

        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }
}
