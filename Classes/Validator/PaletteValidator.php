<?php

namespace PunktDe\Typo3YamlLoader\Validator;

use Symfony\Component\Validator\Constraints as Assert;

class PaletteValidator extends AbstractValidator
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
                        'description' => new Assert\Optional(new Assert\NotBlank()),
                        'displayCond' => new Assert\Optional(
                            new Assert\AtLeastOneOf([
                                new Assert\Type('string'),
                                new Assert\Type('array')
                            ])
                        ),
                        'exclude' => new Assert\Optional(new Assert\Type('bool')),
                        'l10n_display' => new Assert\Optional(
                            new Assert\AtLeastOneOf([
                                new Assert\EqualTo('hideDiff'),
                                new Assert\EqualTo('defaultAsReadonly'),
                            ]),
                        ),
                        'l10n_mode' => new Assert\Optional(
                            new Assert\AtLeastOneOf([
                                new Assert\EqualTo('exclude'),
                                new Assert\EqualTo('prefixLangTitle'),
                            ]),
                        ),
                        'label' => new Assert\Length(['min' => 1]),
                        'onChange' => new Assert\Optional(
                            new Assert\EqualTo('reload')
                        ),
                        'config' => new Assert\Optional([
                            new Assert\Collection([
                                'fields' => [
                                    'type' => new Assert\Length(['min' => 1]),
                                ],
                                'allowExtraFields' => true,
                            ])
                        ])
                    ])
                ])
            ])
        ]);
    }
}
