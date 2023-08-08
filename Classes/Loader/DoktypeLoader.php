<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Loader;

use PunktDe\Typo3YamlLoader\Exception\YamlConfigException;
use PunktDe\Typo3YamlLoader\Converter\ArrayToTyposcriptConverter;
use PunktDe\Typo3YamlLoader\Helper\IconRegistryHelper;
use PunktDe\Typo3YamlLoader\Helper\TcaShowitemHelper;
use PunktDe\Typo3YamlLoader\Validator\DoktypeValidator;
use PunktDe\Typo3YamlLoader\Validator\PalletteValidator;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DoktypeLoader implements SingletonInterface
{
    /**
     * @param string $extensionKey
     * @param mixed[] $doktypeConfigurations
     * @param mixed[] $paletteConfigurations
     * @throws YamlConfigException
     */
    public function __construct(
        string $extensionKey,
        private array $doktypeConfigurations = [],
        private array $paletteConfigurations = [],
    )
    {
        $validationResults = [];

        $extensionPath = GeneralUtility::getFileAbsFileName(sprintf('EXT:%s/Configuration/Doktypes', $extensionKey));
        $doktypeFiles = glob($extensionPath . '/Pages/*.yaml');
        $paletteFiles = glob($extensionPath . '/Palettes/*.yaml');

        if (!$doktypeFiles && !$paletteFiles) return;

        if (!empty($paletteFiles) && is_array($paletteFiles)) {
            $this->loadAndValidateConfigurationFiles($paletteFiles, GeneralUtility::makeInstance(PalletteValidator::class), $this->paletteConfigurations, $validationResults);
        }
        if (!empty($doktypeFiles) && is_array($doktypeFiles)) {
            $this->loadAndValidateConfigurationFiles($doktypeFiles, GeneralUtility::makeInstance(DoktypeValidator::class), $this->doktypeConfigurations, $validationResults);
        }

        // Load Icons
        foreach ($this->doktypeConfigurations as $identifier => $configuration) {
            foreach ($configuration['icons'] as $type => $icon) {
                $iconRegistryHelper = GeneralUtility::makeInstance(IconRegistryHelper::class);
                $iconIdentifier = $iconRegistryHelper->registerIcon($identifier, $icon, $type);
                $this->doktypeConfigurations[$identifier]['icons'][$type] = $iconIdentifier;
            }
        }

        if (!empty($validationResults)) {
            throw new YamlConfigException(
                sprintf('Doktype YAML config is missing required fields: %s', json_encode($validationResults))
            );
        }
    }

    /**
     * @param mixed[] $configFiles
     * @param PalletteValidator|DoktypeValidator $validator
     * @param mixed[] $configStorage
     * @param mixed[] $validationResults
     * @return void
     */
    private function loadAndValidateConfigurationFiles(array $configFiles, PalletteValidator|DoktypeValidator $validator, array &$configStorage, array &$validationResults): void
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
        foreach ($this->doktypeConfigurations as $identifier => $configuration) {
            $doktype = $configuration['backendLayout']['doktype'];

            $configForPageTS = [
                'title' => $configuration['title'],
                'config' => [
                    'backend_layout' => $configuration['backendLayout']
                ]
            ];

            ExtensionManagementUtility::addPageTSConfig(
                ArrayToTyposcriptConverter::convertArrayToTypoScript($configForPageTS, sprintf('mod.web_layout.BackendLayouts.%s', $identifier))
            );
            ExtensionManagementUtility::addUserTSConfig(
                'options.pageTree.doktypesToShowInNewPageDragArea := addToList(' . $doktype . ')'
            );
        }
    }

    public function loadPageTypes(): void
    {
        foreach ($this->doktypeConfigurations as $identifier => $configuration) {

            $doktype = $configuration['backendLayout']['doktype'];

            $GLOBALS['PAGES_TYPES'][$doktype] = [
                'type' => 'web',
                'allowedTables' => '*',
            ];
        }
    }

    public function loadTcaOverrides(): void
    {

        foreach ($this->paletteConfigurations as $identifier => $configuration) {
            $columnConfig = [];
            $paletteConfiguration = [
                'label' => $configuration['label'],
                'showitem' => ''
            ];

            foreach ($configuration['columns'] as $columnIdentifier => $columnConfiguration) {
                if (is_array($columnConfiguration) && !empty($columnConfiguration)) {
                    $columnConfig[$columnIdentifier] = [
                        'exclude' => $columnConfiguration['exclude'] ?? false,
                        'label' => $columnConfiguration['label'],
                        'config' => $columnConfiguration['config']
                    ];

                }
                $paletteConfiguration['showitem'] .= $columnIdentifier . ';,';
            }

            ExtensionManagementUtility::addTCAcolumns('pages', $columnConfig);
            $GLOBALS['TCA']['pages']['palettes'][$identifier] = $paletteConfiguration;
        }

        foreach ($this->doktypeConfigurations as $identifier => $configuration) {
            $title = $configuration['title'];
            $doktype = $configuration['backendLayout']['doktype'];

            $iconIdentifierDefault = $configuration['icons']['default'];
            $iconIdentifierHidden = $configuration['icons']['hidden'];

            ExtensionManagementUtility::addTcaSelectItem(
                'pages','doktype', [$title, $doktype, $iconIdentifierDefault], '1', 'before'
            );

            $showItem = $GLOBALS['TCA']['pages']['types'][PageRepository::DOKTYPE_DEFAULT]['showitem'];
            $tcaShowitemHelper = GeneralUtility::makeInstance(TcaShowitemHelper::class);


            if (array_key_exists('ui', $configuration)) {
                $keepExisting = array_key_exists('keepExisting', $configuration['ui']) ? $configuration['ui']['keepExisting'] : true;

                $showitemConfiguration = $tcaShowitemHelper->parseShowitemConfig($identifier, $configuration['ui']['tabs']);

                if ($keepExisting) {
                     $showItem = $GLOBALS['TCA']['pages']['types'][PageRepository::DOKTYPE_DEFAULT]['showitem'] . ', ' . $showitemConfiguration;
                }
            }


            ArrayUtility::mergeRecursiveWithOverrule(
                $GLOBALS['TCA']['pages'],
                [
                    'ctrl' => [
                        'typeicon_classes' => [
                            $doktype => $iconIdentifierDefault,
                            $doktype . '-root' => 'apps-pagetree-page-domain',
                            $doktype . '-hideinmenu' => $iconIdentifierHidden,
                        ],
                    ],
                    'types' => [
                        $doktype => [
                            'showitem' => $showItem
                        ]
                    ]
                ],
            );
        }
    }
}
