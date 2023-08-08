<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Helper;

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaShowitemHelper implements SingletonInterface
{

    /**
     * @param string $identifier
     * @param mixed[] $tabConfiguration
     * @param string $table
     * @return string
     */
    public function parseShowitemConfig(string $identifier, array $tabConfiguration, string $table = 'tt_content'): string
    {
        $showItem = [];

        foreach ($tabConfiguration as $tab) {
            $label = $tab['label'];

            $showItem[] = '--div--;' . $label;
            foreach ($tab['palettes'] as $paletteIdentifier => $paletteConfiguration) {
                if (empty($paletteConfiguration)) {
                    $showItem[] = '--palette--;;' . $paletteIdentifier;
                } else {
                    if (array_key_exists($paletteIdentifier, $GLOBALS['TCA'][$table]['palettes'])) {
                        $showItem[] = '--palette--;;' . $paletteIdentifier;
                    } else {
                        $showItem[] = $paletteIdentifier . ';' . $paletteConfiguration['label'];
                    }

                    if (
                        array_key_exists('columnsOverrides', $paletteConfiguration)
                        && !empty($paletteConfiguration['columnsOverrides'])
                    ) {
                        foreach($paletteConfiguration['columnsOverrides'] as $column => $override) {
                            $GLOBALS['TCA'][$table]['types'][$identifier]['columnsOverrides'][$column] = $override;
                        }
                    }
                }
            }
        }

        return implode(",\n", $showItem);
    }
}
