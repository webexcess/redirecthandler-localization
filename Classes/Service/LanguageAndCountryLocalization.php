<?php

namespace WebExcess\RedirectHandler\Localization\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request;
use Neos\Flow\I18n\Detector;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

/**
 * @Flow\Scope("singleton")
 */
class LanguageAndCountryLocalization implements LocalizationInterface
{

    /**
     * @Flow\InjectConfiguration("dimensionIdentifiers")
     * @var array
     */
    protected $dimensionIdentifiers;

    /**
     * @Flow\InjectConfiguration("overwriteDefaultDimensionUriSegment")
     * @var array
     */
    protected $overwriteDefaultDimensionUriSegment;

    /**
     * @Flow\Inject
     * @var Detector
     */
    protected $localeDetector;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\InjectConfiguration("ipinfoToken")
     * @var string
     */
    protected $ipinfoToken;

    /**
     * @param Request $httpRequest
     * @return array
     */
    public function getTargetContext(Request $httpRequest)
    {
        $dimensionNamesAndPresets = [];

        $locale = $this->localeDetector->detectLocaleFromHttpHeader($httpRequest->getHeader('Accept-Language'));
        $dimensionNamesAndPresets[$this->dimensionIdentifiers['linguistic']] = $this->contentDimensionPresetSource->findPresetByUriSegment(
            $this->dimensionIdentifiers['linguistic'],
            $locale->getLanguage()
        );

        $clientIpAddress = $httpRequest->getClientIpAddress();
        if (substr($clientIpAddress, 0, 3) == '192' || $clientIpAddress == '127.0.0.1') {
            // looks like you'r in a local vm. fetch a public ip..
            $dyndnsResponse = file_get_contents('http://checkip.dyndns.com/');
            preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $dyndnsResponse, $dyndnsMatches);
            $clientIpAddress = $dyndnsMatches[1];
        }

        $countryCode = $this->getCountryCodeByIpAddress($clientIpAddress);
        $dimensionNamesAndPresets[$this->dimensionIdentifiers['regional']] = $this->contentDimensionPresetSource->findPresetByUriSegment(
            $this->dimensionIdentifiers['regional'],
            $countryCode
        );

        $isPresetCombinationAllowed = $this->contentDimensionPresetSource->isPresetCombinationAllowedByConstraints(array(
            $this->dimensionIdentifiers['linguistic'] => $dimensionNamesAndPresets[$this->dimensionIdentifiers['linguistic']]['identifier'],
            $this->dimensionIdentifiers['regional']   => $dimensionNamesAndPresets[$this->dimensionIdentifiers['regional']]['identifier'],
        ));

        if (!$isPresetCombinationAllowed) {
            $dimensionNamesAndPresets[$this->dimensionIdentifiers['linguistic']] = $this->getDefaultLanguageInCountryPreset(
                $dimensionNamesAndPresets[$this->dimensionIdentifiers['regional']]['constraints'][$this->dimensionIdentifiers['linguistic']]
            );
        }

        return [
            'dimensions'       => [
                'language' => $dimensionNamesAndPresets['language']['values'],
                'country'  => $dimensionNamesAndPresets['country']['values'],
            ],
            'targetDimensions' => [
                'language' => $dimensionNamesAndPresets['language']['identifier'],
                'country'  => $dimensionNamesAndPresets['country']['identifier'],
            ],
        ];
    }

    /**
     * @param string $ipAddress
     * @return mixed|string
     */
    public function getCountryCodeByIpAddress($ipAddress)
    {
        $countryCode = '';

        if (isset($this->ipinfoToken)) {
            $ipinfoUrl = 'http://ipinfo.io/' . $ipAddress . '/geo';
            $ipinfoUrl .= (!empty($this->ipinfoToken) ? '?token=' . $this->ipinfoToken : '');

            $geoData = json_decode(file_get_contents($ipinfoUrl), true);
            if (!is_null($geoData) && array_key_exists('country', $geoData)) {
                $countryCode = $geoData['country'];
            }
        }

        if (empty($countryCode) && function_exists('geoip_country_code_by_name')) {
            $countryCode = \geoip_country_code_by_name($ipAddress);
        }

        if (empty($countryCode)) {
            $countryCode = $this->overwriteDefaultDimensionUriSegment[$this->dimensionIdentifiers['regional']];
        }

        return $countryCode;
    }

    /**
     * @param array $possibleLanguages
     * @return array
     */
    protected function getDefaultLanguageInCountryPreset(array $possibleLanguages)
    {
        $dimensionIdentifier = null;
        $fallbackDimensionIdentifiers = $possibleLanguages;
        foreach ($fallbackDimensionIdentifiers as $fallbackDimensionIdentifier => $active) {
            if ($active) {
                $dimensionIdentifier = $fallbackDimensionIdentifier;
                break;
            }
        }

        return $this->contentDimensionPresetSource->findPresetByUriSegment(
            $this->dimensionIdentifiers['linguistic'],
            $dimensionIdentifier
        );
    }

}
