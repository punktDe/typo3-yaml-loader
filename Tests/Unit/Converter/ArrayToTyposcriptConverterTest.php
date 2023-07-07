<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Tests\Unit\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PunktDe\Typo3YamlLoader\Converter\ArrayToTyposcriptConverter;

class ArrayToTyposcriptConverterTest extends UnitTestCase
{

    /**
     * @return mixed[]
     */
    public static function tsArrayDataProvider(): array
    {
        return [
            [
                ['foo' => 'bar'],
                "foo = bar\n",
            ],
            [
                ['test' => ['foo' => 'bar']],
                "prefix {"
                . "\n\ttest {"
                . "\n\t\tfoo = bar"
                . "\n\t}"
                . "\n}",
                'prefix'
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param string $expected
     * @param string $prefix
     * @return void
     */
    #[DataProvider('tsArrayDataProvider')]
    public function testArrayIsConvertedToTyposcript(array $data, string $expected, string $prefix = ''): void
    {
        $result = ArrayToTyposcriptConverter::convertArrayToTypoScript($data, $prefix);
        $this->assertSame($expected, $result);
    }
}
