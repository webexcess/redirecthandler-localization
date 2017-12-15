<?php

namespace WebExcess\RedirectHandler\Localization\Aspects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

use Neos\Flow\Http\Headers;
use Neos\Flow\Http\Request as Request;
use Neos\Flow\Http\Response;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\RedirectHandler\Exception;
use Neos\RedirectHandler\RedirectInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class RedirectServiceAspect
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
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\Around("method(Neos\RedirectHandler\RedirectService->buildResponse())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     */
    public function replaceBuildResponse(JoinPointInterface $joinPoint)
    {
        $httpRequest = $joinPoint->getMethodArgument('httpRequest');
        $redirect = $joinPoint->getMethodArgument('redirect');

        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        try {
            return $this->buildResponse($httpRequest, $redirect);
        } catch (\Exception $exception) {
            return $result;
        }
    }

    /**
     * @param Request $httpRequest
     * @param RedirectInterface $redirect
     * @return Response|null
     * @throws Exception
     */
    protected function buildResponse(Request $httpRequest, RedirectInterface $redirect)
    {

        if (headers_sent() === true && FLOW_SAPITYPE !== 'CLI') {
            return null;
        }

        $response = new Response();
        $statusCode = $redirect->getStatusCode();
        $response->setStatus($statusCode);

        if ($statusCode >= 300 && $statusCode <= 399) {
            $location = $redirect->getTargetUriPath();

            if (strpos($location, 'node://') === 0) {
                $location = $this->getDimensionUriSegment() . '/localization-redirect/' . substr($location, 7);
            }

            if (parse_url($location, PHP_URL_SCHEME) === null) {
                $location = $httpRequest->getBaseUri() . $location;
            }

            $response->setHeaders(new Headers([
                'Location'      => $location,
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                'Expires'       => 'Sat, 26 Jul 1997 05:00:00 GMT',
            ]));
        } elseif ($statusCode >= 400 && $statusCode <= 599) {
            $exception = new Exception();
            $exception->setStatusCode($statusCode);
            throw $exception;
        }

        return $response;
    }

    protected function getDimensionUriSegment()
    {
        $dimensionUriSegment = '';
        foreach ($this->dimensionIdentifiers as $dimensionIdentifier) {
            if (isset($this->overwriteDefaultDimensionUriSegment[$dimensionIdentifier])) {
                $segment = $this->overwriteDefaultDimensionUriSegment[$dimensionIdentifier];
            } else {
                $segment = $this->contentDimensionPresetSource->getDefaultPreset($dimensionIdentifier)['uriSegment'];
            }

            if (!empty($segment)) {
                $dimensionUriSegment .= (empty($dimensionUriSegment) ? '' : '_') . $segment;
            }
        }
        return $dimensionUriSegment;
    }
}
