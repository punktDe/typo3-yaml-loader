<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Tests\Unit\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PunktDe\Typo3YamlLoader\Helper\IconRegistryHelper;
use ReflectionException;
use ReflectionMethod;

class IconRegistryHelperTest extends UnitTestCase
{

    /**
     * @return mixed[]
     */
    public static function iconDataProvider(): array
    {
        return [
            ['default_page','EXT:site_package/Resources/Public/Icons/foo.svg', 'hidden', 'default_page-hidden'],
            ['default_page','EXT:site_package/Resources/Public/Icons/foo.svg', null, 'default_page'],
            ['default_page','app-content', null, 'app-content'],
            ['default_page','app-content', 'hidden', 'app-content'],
        ];
    }

    /**
     * @throws ReflectionException
     */
    #[DataProvider('iconDataProvider')]
    public function testGeneratedIconIdentifier(string $entityIdentifier, string $iconIdentifier, ?string $modifier, string $expected): void
    {
        $method = new ReflectionMethod(
            IconRegistryHelper::class, 'getIconIdentifier'
        );

        $this->assertSame(
            $expected, $method->invoke(new IconRegistryHelper, $entityIdentifier, $iconIdentifier, $modifier)
        );
    }
}
