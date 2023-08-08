<?php

namespace PunktDe\Typo3YamlLoader\Validator;

use Symfony\Component\Validator\Constraints as Assert;

class ContentTypeValidator extends AbstractValidator
{
    protected function getConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'iconIdentifier' => new Assert\NotBlank(),
            'title'  => new Assert\NotBlank(),
            'description' => new Assert\NotBlank(),
            'ui' => new Assert\Optional([
                new Assert\Type('array')
            ])
        ]);
    }
}
