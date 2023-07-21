<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Loader;

use PunktDe\Typo3YamlLoader\Exception\YamlConfigException;
use PunktDe\Typo3YamlLoader\Converter\ArrayToTyposcriptConverter;
use PunktDe\Typo3YamlLoader\Helper\IconRegistryHelper;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentTypeLoader implements SingletonInterface
{
    const REQUIRED_CONTENT_TYPE_STRUCTURE = [
        'iconIdentifier' => null,
        'title' => null,
        'description' => null,
    ];

    /**
     * @param string $extensionKey
     * @param mixed[] $configurations
     * @throws YamlConfigException
     */
    public function __construct(
        string        $extensionKey,
        private array $configurations = [],
    )
    {
        $extensionPath = GeneralUtility::getFileAbsFileName(sprintf('EXT:%s/Configuration/ContentTypes', $extensionKey));
        $contentTypeFiles = glob($extensionPath . '/*.yaml');

        if (!$contentTypeFiles) return;

        foreach ($contentTypeFiles as $file) {
            if (!is_file($file)) continue;

            $contents = file_get_contents($file);
            if (!$contents) continue;

            $loadedConfig = YAML::parse($contents);
            $this->configurations = array_merge($this->configurations, $loadedConfig);
            foreach ($this->configurations as $identifier => $configuration) {
                $validationResult = self::validateDoktypeConfig(self::REQUIRED_CONTENT_TYPE_STRUCTURE, $configuration);

                if (!empty($validationResult)) {
                    $validationResults[$identifier] = $validationResult;
                    continue;
                }

                $iconRegistryHelper = GeneralUtility::makeInstance(IconRegistryHelper::class);
                $iconIdentifier = $iconRegistryHelper->registerIcon($identifier, $configuration['iconIdentifier']);
                $this->configurations[$identifier]['iconIdentifier'] = $iconIdentifier;
            }
        }

        if (!empty($validationResults)) {
            throw new YamlConfigException(
                sprintf('Content-Type YAML config is missing required fields: %s', json_encode($validationResults))
            );
        }
    }

    public function loadPageTS(): void
    {
        foreach ($this->configurations as $identifier => $configuration) {
            $elementConfig = [
                'elements' => [
                    $identifier => [
                        'iconIdentifier' => $configuration['iconIdentifier'],
                        'title' => $configuration['title'],
                        'description' => $configuration['description'],
                        'tt_content_defValues' => [
                            'CType' => $identifier
                        ]
                    ]
                ],
            ];

            ExtensionManagementUtility::addPageTSConfig(
                ArrayToTyposcriptConverter::convertArrayToTypoScript($elementConfig, 'mod.wizards.newContentElement.wizardItems.common')
            );
            ExtensionManagementUtility::addPageTSConfig(
                sprintf('mod.wizards.newContentElement.wizardItems.common.show := addToList(%s)', $identifier)
            );
        }
    }

    public function loadTcaOverrides(): void
    {
        foreach ($this->configurations as $identifier => $configuration) {
            ExtensionManagementUtility::addTcaSelectItem(
                'tt_content',
                'CType',
                [
                    $configuration['title'],
                    $identifier,
                    $configuration['iconIdentifier'],
                ],
                'text',
                'after'
            );

            $showItem = [];

            foreach ($configuration['ui']['tabs'] as $tab) {
                $label = $tab['label'];

                $showItem[] = '--div--;' . $label;
                foreach ($tab['palettes'] as $paletteIdentifier => $paletteConfiguration) {
                    if (empty($paletteConfiguration)) {
                        $showItem[] = '--palette--;;' . $paletteIdentifier;
                    } else {
                        if (array_key_exists($paletteIdentifier, $GLOBALS['TCA']['tt_content']['palettes'])) {
                            $showItem[] = '--palette--;;' . $paletteIdentifier;
                        } else {
                            $showItem[] = $paletteIdentifier . ';' . $paletteConfiguration['label'];
                        }

                        if (
                            array_key_exists('columnsOverrides', $paletteConfiguration)
                            && !empty($paletteConfiguration['columnsOverrides'])
                        ) {
                            $GLOBALS['TCA']['tt_content']['types'][$identifier]['columnsOverrides'][$paletteIdentifier]['config'] = $paletteConfiguration['columnsOverrides'];

                        }
                    }
                }
            }

            $GLOBALS['TCA']['tt_content']['types'][$identifier]['showitem'] = implode(",\n", $showItem);
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$identifier] = $configuration['iconIdentifier'];
        }
    }

    /**
     * @param mixed[] $structure
     * @param mixed[] $data
     * @return mixed[]
     */
    private static function validateDoktypeConfig(array $structure, array $data): array
    {
        $missingKeys = [];
        foreach ($structure as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $missingKeys[] = $key;
            }
            if (is_array($value)) {
                $result = self::validateDoktypeConfig($value, $data[$key]);
                if (!empty($result)) {
                    $missingKeys[$key] = $result;
                }
            }
        }
        return $missingKeys;
    }
}
