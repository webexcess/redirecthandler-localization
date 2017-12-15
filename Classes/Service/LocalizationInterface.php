<?php

namespace WebExcess\RedirectHandler\Localization\Service;

use Neos\Flow\Http\Request;

interface LocalizationInterface
{

    /**
     * @param Request $httpRequest
     * @return array
     */
    public function getTargetContext(Request $httpRequest);

}
