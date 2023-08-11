<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Loader;

use PunktDe\Typo3YamlLoader\Exception\YamlConfigException;
use PunktDe\Typo3YamlLoader\Converter\ArrayToTyposcriptConverter;
use PunktDe\Typo3YamlLoader\Helper\IconRegistryHelper;
use PunktDe\Typo3YamlLoader\Helper\TcaShowitemHelper;
use PunktDe\Typo3YamlLoader\Validator\ContentTypeValidator;
use PunktDe\Typo3YamlLoader\Validator\PaletteValidator;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function PHPUnit\Framework\throwException;

class ContentTypeLoader implements SingletonInterface
{
    protected TcaShowitemHelper $tcaShowitemHelper;

    /**
     * @param string $extensionKey
     * @param mixed[] $contentTypeConfigurations
     * @param mixed[] $paletteConfigurations
     * @throws YamlConfigException
     */
    public function __construct(
        private readonly string $extensionKey,
        private array $contentTypeConfigurations = [],
        private array $paletteConfigurations = [],
    )
    {
        $validationResults = [];

        $this->tcaShowitemHelper = GeneralUtility::makeInstance(TcaShowitemHelper::class);

        $extensionPath = GeneralUtility::getFileAbsFileName(sprintf('EXT:%s/Configuration/ContentTypes', $extensionKey));
        $contentTypeFiles = glob($extensionPath . '/Elements/*.yaml');
        $paletteFiles = glob($extensionPath . '/Palettes/*.yaml');

        if (!$contentTypeFiles && !$paletteFiles) return;
        if (!empty($paletteFiles) && is_array($paletteFiles)) {
            $this->loadAndValidateConfigurationFiles($paletteFiles, GeneralUtility::makeInstance(PaletteValidator::class), $this->paletteConfigurations, $validationResults);
        }
        if (!empty($contentTypeFiles) && is_array($contentTypeFiles)) {
            $this->loadAndValidateConfigurationFiles($contentTypeFiles, GeneralUtility::makeInstance(ContentTypeValidator::class), $this->contentTypeConfigurations, $validationResults);
        }

        if (!empty($validationResults)) {
            throw new YamlConfigException(
                sprintf('Content-Type YAML config is missing required fields: %s', json_encode($validationResults))
            );
        }
    }

    /**
     * @param mixed[] $configFiles
     * @param ContentTypeValidator $validator
     * @param mixed[] $configStorage
     * @param mixed[] $validationResults
     * @return void
     */
    private function loadAndValidateConfigurationFiles(array $configFiles, PaletteValidator|ContentTypeValidator $validator, array &$configStorage, array &$validationResults): void
    {
        foreach ($configFiles as $configFile) {
            if (!is_file($configFile)) continue;

            $fileContents = file_get_contents($configFile);
            if (!$fileContents) continue;

            $config = YAML::parse($fileContents);
            // Validate
            $validationResult = $validator->validate($config[array_key_first($config)]);
            if (!empty($validationResult)) {
                $validationResults[array_key_first($config)] = $validationResult;
                continue;
            }
            $configStorage = array_merge($configStorage, $config);
        }
    }

    public function loadPageTS(): void
    {
        foreach ($this->contentTypeConfigurations as $identifier => $configuration) {
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
        foreach ($this->paletteConfigurations as $identifier => $configuration) {
            $columns = [];

            foreach($configuration['columns'] as $columnIdentifier => $columnConfiguration) {
                $columns[$columnIdentifier] = [
                    'exclude' => $columnConfiguration['exclude'] ?? 0,
                    'label' => $columnConfiguration['label'],
                ];

                if (array_key_exists('config', $columnConfiguration)) {
                    $columns[$columnIdentifier]['config'] = $columnConfiguration['config'];
                }
            }

            ArrayUtility::mergeRecursiveWithOverrule(
                $GLOBALS['TCA']['tt_content']['columns'], $columns
            );

            // Add palettes to TCA/tt_content/palettes
            $paletteConfiguration = [
                'label' => $configuration['label'],
                'showitem' => implode(',--linebreak--,', array_map(function ($el) { return $el . ';';}, array_keys($columns)))
            ];

            $GLOBALS['TCA']['tt_content']['palettes'][$identifier] = $paletteConfiguration;
        }

        foreach ($this->contentTypeConfigurations as $identifier => $configuration) {
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

            $showitem = '';

            if (array_key_exists('ui', $configuration)) {
                $showitem = $this->tcaShowitemHelper->parseShowitemConfig($identifier, $configuration['ui']['tabs']);
            }

            ArrayUtility::mergeRecursiveWithOverrule(
                $GLOBALS['TCA']['tt_content'],
                [
                    'ctrl' => [
                        'typeicon_classes' => [
                            $identifier => $configuration['iconIdentifier']
                        ]
                    ],
                    'types' => [
                        $identifier => [
                            'showitem' => $showitem
                        ]
                    ]
                ]
            );
        }
    }
}
