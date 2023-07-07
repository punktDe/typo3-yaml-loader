<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Loader;

use PunktDe\Typo3YamlLoader\Exception\YamlConfigException;
use PunktDe\Typo3YamlLoader\Converter\ArrayToTyposcriptConverter;
use PunktDe\Typo3YamlLoader\Helper\IconRegistryHelper;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DoktypeLoader implements SingletonInterface
{
    const REQUIRED_DOKTYPE_STRUCTURE = [
        'icons' => [
            'default' => NULL,
            'hidden' => NULL,
        ],
        'title' => NULL,
        'backendLayout' => [
            'doktype' => NULL,
            'rowCount' => NULL,
            'colCount' => NULL,
        ],
    ];

    /**
     * @param string $extensionKey
     * @param mixed[] $configurations
     * @throws YamlConfigException
     */
    public function __construct(
        string $extensionKey,
        private array $configurations = [],
    )
    {
        $validationResults = [];

        $extensionPath = GeneralUtility::getFileAbsFileName(sprintf('EXT:%s/Configuration/Doktypes', $extensionKey));
        $doktypeFiles = glob($extensionPath . '/*.yaml');

        if (!$doktypeFiles) return;

        foreach ($doktypeFiles as $file) {
            if (!is_file($file)) continue;

            $contents = file_get_contents($file);
            if (!$contents) continue;

            $loadedConfig = YAML::parse($contents);
            $this->configurations = array_merge($this->configurations, $loadedConfig);
            foreach ($this->configurations as $identifier => $configuration) {
                $validationResult = self::validateDoktypeConfig(self::REQUIRED_DOKTYPE_STRUCTURE, $configuration);

                if (!empty($validationResult)) {
                    $validationResults[$identifier] = $validationResult;
                    continue;
                }

                foreach ($configuration['icons'] as $type => $icon) {
                    $iconRegistryHelper = GeneralUtility::makeInstance(IconRegistryHelper::class);
                    $iconIdentifier = $iconRegistryHelper->registerIcon($identifier, $icon, $type);
                    $this->configurations[$identifier]['icons'][$type] = $iconIdentifier;
                }
            }
        }

        if (!empty($validationResults)) {
            throw new YamlConfigException(
                sprintf('Doktype YAML config is missing required fields: %s', json_encode($validationResults))
            );
        }
    }

    public function loadPageTS(): void
    {
        foreach ($this->configurations as $identifier => $configuration) {
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
        foreach ($this->configurations as $identifier => $configuration) {

            $doktype = $configuration['backendLayout']['doktype'];

            $GLOBALS['PAGES_TYPES'][$doktype] = [
                'type' => 'web',
                'allowedTables' => '*',
            ];
        }
    }

    public function loadTcaOverrides(): void
    {
        foreach ($this->configurations as $identifier => $configuration) {
            $title = $configuration['title'];
            $doktype = $configuration['backendLayout']['doktype'];

            $iconIdentifierDefault = $configuration['icons']['default'];
            $iconIdentifierHidden = $configuration['icons']['hidden'];

            ExtensionManagementUtility::addTcaSelectItem(
                'pages','doktype', [$title, $doktype, $iconIdentifierDefault], '1', 'before'
            );

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
                            'showitem' => $GLOBALS['TCA']['pages']['types'][PageRepository::DOKTYPE_DEFAULT]['showitem']
                        ]
                    ]
                ],
            );
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
