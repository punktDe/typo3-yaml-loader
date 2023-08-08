<?php

namespace PunktDe\Typo3YamlLoader\Validator;

use Symfony\Component\Validator\Constraints as Assert;

class PalletteValidator extends AbstractValidator
{
    protected function getConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'label' => new Assert\NotBlank(),
            'columns' => new Assert\Optional([
                new Assert\Type('array'),
                new Assert\Count(['min' => 1]),
                new Assert\All([
                    new Assert\Collection([
                        'fields' => [
                            'exclude' => new Assert\Optional(new Assert\Type('bool')),
                            'label' => new Assert\Length(['min' => 1]),
                            'config' => new Assert\Collection([
                                'type' => new Assert\Length(['min' => 1])
                            ])
                        ],
                        'allowExtraFields' => true,
                    ])
                ])
            ])
        ]);
    }
}
