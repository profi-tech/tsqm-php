<?php

namespace Examples\Greeter;

use Exception;
use Generator;
use Tsqm\RetryPolicy;
use Tsqm\Task;

class GreetWithRandomFail
{
    private ValidateName $validateName;
    private CreateGreeting $createGreeting;
    private PurchaseWithRandomFail $purchaseWithRandomFail;
    private RevertGreeting $revertGreeting;
    private SendGreeting $sendGreeting;

    public function __construct(
        ValidateName $validateName,
        CreateGreeting $createGreeting,
        PurchaseWithRandomFail $purchaseWithRandomFail,
        RevertGreeting $revertGreeting,
        SendGreeting $sendGreeting
    ) {
        $this->validateName = $validateName;
        $this->createGreeting = $createGreeting;
        $this->purchaseWithRandomFail = $purchaseWithRandomFail;
        $this->revertGreeting = $revertGreeting;
        $this->sendGreeting = $sendGreeting;
    }

    public function __invoke(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }

        $greeting = yield (new Task())
            ->setCallable($this->createGreeting)
            ->setArgs($name);

        try {
            $greeting = yield (new Task())->setCallable($this->purchaseWithRandomFail)
                ->setArgs($greeting)
                ->setIsSecret(true)
                ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3)->setMinInterval(10000));
        } catch (Exception $e) {
            yield (new Task())->setCallable($this->revertGreeting)->setArgs($greeting);
            return false;
        }

        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }
}
