<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Helper;

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IconRegistryHelper implements SingletonInterface
{
    public function registerIcon(string $entityIdentifier, string $iconIdentifier, string $modifier = ''): string
    {
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $customIconIdentifier = $this->getIconIdentifier($entityIdentifier, $iconIdentifier, $modifier);

        if ($iconRegistry->isRegistered($customIconIdentifier)) {
            return $customIconIdentifier;
        }

        $iconRegistry
            ->registerIcon(
                $customIconIdentifier,
                SvgIconProvider::class,
                [
                    'source' => $iconIdentifier,
                ]
            );
            return $customIconIdentifier;
    }

    private function getIconIdentifier(string $entityIdentifier, string $iconIdentifier, ?string $modifier): string
    {
        if (str_starts_with($iconIdentifier, 'EXT:')) {
            return implode('-', array_filter([

                $entityIdentifier,
                $modifier,
            ]));
        }
        return $iconIdentifier;
    }
}
