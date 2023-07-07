<?php
declare(strict_types=1);

namespace PunktDe\Typo3YamlLoader\Converter;
/**
 * Converts given array to TypoScript
 *
 * @param array $typoScriptArray The array to convert to string
 * @param string $addKey Prefix given values with given key (eg. lib.whatever = {...})
 * @param integer $tab Internal
 * @param boolean $init Internal
 * @return string TypoScript
 */

class ArrayToTyposcriptConverter
{
    /**
     * @param mixed[] $typoScriptArray
     * @param string|int $addKey
     * @param int $tab
     * @param bool $init
     * @return string
     */
    public static function convertArrayToTypoScript(array $typoScriptArray, string|int $addKey = '', int $tab = 0, bool $init = true): string
    {
        $typoScript = '';
        if ($addKey !== '') {
            $typoScript .= str_repeat("\t", ($tab === 0) ? $tab : $tab - 1) . $addKey . " {\n";
            if ($init === TRUE) {
                $tab++;
            }
        }
        $tab++;
        foreach ($typoScriptArray as $key => $value) {
            if (!is_array($value)) {
                if (!is_string($value)) {
                    $value = (string)$value;
                }
                if (!str_contains($value, "\n")) {
                    $typoScript .= str_repeat("\t", ($tab === 0) ? $tab : $tab - 1) . "$key = $value\n";
                } else {
                    $typoScript .= str_repeat("\t", ($tab === 0) ? $tab : $tab - 1) . "$key (\n$value\n" . str_repeat("\t", ($tab === 0) ? $tab : $tab - 1) . ")\n";
                }

            } else {
                $typoScript .= self::convertArrayToTypoScript($value, $key, $tab, FALSE);
            }
        }
        if ($addKey !== '') {
            $tab--;
            $typoScript .= str_repeat("\t", ($tab === 0) ? $tab : $tab - 1) . '}';
            if ($init !== TRUE) {
                $typoScript .= "\n";
            }
        }
        return $typoScript;
    }
}
