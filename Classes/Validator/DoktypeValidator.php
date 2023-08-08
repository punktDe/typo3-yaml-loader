<?php

namespace PunktDe\Typo3YamlLoader\Validator;

use Symfony\Component\Validator\Constraints as Assert;

class DoktypeValidator extends AbstractValidator
{
    protected function getConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'icons' => new Assert\Collection([
                'default' => new Assert\NotBlank(),
                'hidden' => new Assert\NotBlank()
            ]),
            'title' => new Assert\NotBlank(),
            'backendLayout' => new Assert\Collection([
                'doktype' => new Assert\Type('numeric'),
                'rowCount' => new Assert\Type('numeric'),
                'colCount' => new Assert\Type('numeric'),
                'rows' => new Assert\Optional([
                    new Assert\Type('array')
                ]),
            ]),
            'ui' => new Assert\Optional([
                new Assert\Collection([
                    'keepExisting' => new Assert\Optional([
                        new Assert\Type('bool')
                    ]),
                    'tabs' => new Assert\All([
                        new Assert\Collection([
                            'label' => new Assert\NotBlank(),
                            'palettes' => new Assert\Type('array'),
                        ])
                    ]),
                ]),
            ]),
        ]);
    }
}
