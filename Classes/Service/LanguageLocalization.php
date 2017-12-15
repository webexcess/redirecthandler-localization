<?php

namespace WebExcess\RedirectHandler\Localization\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request;
use Neos\Flow\I18n\Detector;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

/**
 * @Flow\Scope("singleton")
 */
class LanguageLocalization implements LocalizationInterface
{

    /**
     * @Flow\Inject
     * @var Detector
     */
    protected $localeDetector;

    /**
     * @Flow\InjectConfiguration("dimensionIdentifiers")
     * @var array
     */
    protected $dimensionIdentifiers;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

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

        return [
            'dimensions'       => [
                'language' => $dimensionNamesAndPresets['language']['values'],
            ],
            'targetDimensions' => [
                'language' => $dimensionNamesAndPresets['language']['identifier'],
            ],
        ];
    }

}
