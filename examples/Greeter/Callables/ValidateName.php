<?php
namespace Examples\Greeter\Callables;

use Examples\Greeter\Validator;

class ValidateName {

    private Validator $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    public function __invoke(string $name): bool
    {
        return $this->validator->validateName($name);
    }
}