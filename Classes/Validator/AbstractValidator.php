<?php

namespace PunktDe\Typo3YamlLoader\Validator;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractValidator
{
    abstract protected function getConstraints(): Assert\Collection;

    /**
     * @param mixed[] $data
     * @return string[]
     */
    public function validate(array $data): array
    {
        $return = [];
        $validator = Validation::createValidator();

        $violations = $validator->validate($data, $this->getConstraints());

        /** @var ConstraintViolationInterface $violation */
        foreach($violations as $violation) {
            $return[$violation->getPropertyPath()] = $violation->getMessage();
        }
        return $return;
    }
}
