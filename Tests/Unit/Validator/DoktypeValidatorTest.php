<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Tests\Unit\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PunktDe\Typo3YamlLoader\Validator\DoktypeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PunktDe\Typo3YamlLoader\Helper\IconRegistryHelper;
use ReflectionException;
use ReflectionMethod;

class DoktypeValidatorTest extends UnitTestCase
{

    /**
     * @return mixed[]
     */
    public static function doktypeConfigDataProvider(): array
    {
        return [
            [
                [
                    'icons' => [
                        'default' => 'icon-identifier',
                        'hidden' => 'icon-identifier'
                    ],
                    'backendLayout' => 'foobar'
                ],
                [
                    '[title]' => 'This field is missing.',
                    '[backendLayout]' => 'This value should be of type array|(Traversable&ArrayAccess).'
                ]
            ],
            [
                [
                    'icons' => [
                        'default' => 'icon-identifier',
                        'hidden' => 'icon-identifier'
                    ],
                    'backendLayout' => []
                ],
                [
                    '[title]' => 'This field is missing.',
                    '[backendLayout][doktype]' => 'This field is missing.',
                    '[backendLayout][rowCount]' => 'This field is missing.',
                    '[backendLayout][colCount]' => 'This field is missing.',
                ]
            ],
            [
                [
                    'icons' => [
                        'default' => 'icon-identifier',
                        'hidden' => 'icon-identifier'
                    ],
                    'title' => 'title-string',
                    'backendLayout' => [
                        'doktype' => 100,
                        'rowCount' => 1,
                        'colCount' => 1,
                    ]
                ],
                []
            ]
        ];
    }

    /**
     * @throws ReflectionException
     */
    #[DataProvider('doktypeConfigDataProvider')]
    public function testValidateDoktypeConfiguration(array $doktypeConfiguration, array $expectedValidationResults): void
    {
        $doktypeValidator = new DoktypeValidator();
        $validationResults = $doktypeValidator->validate($doktypeConfiguration);
        $this->assertEquals($validationResults, $expectedValidationResults);
    }
}
