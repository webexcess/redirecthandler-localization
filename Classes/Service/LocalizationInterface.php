<?php

namespace WebExcess\RedirectHandler\Localization\Service;

use GuzzleHttp\Psr7\ServerRequest as Request;

interface LocalizationInterface
{

    /**
     * @param Request $httpRequest
     * @return array
     */
    public function getTargetContext(Request $httpRequest);

}
