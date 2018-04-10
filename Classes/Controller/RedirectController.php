<?php
namespace WebExcess\RedirectHandler\Localization\Controller;

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Neos\Service\LinkingService;
use Neos\Neos\Exception;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use WebExcess\RedirectHandler\Localization\Service\LocalizationInterface;

class RedirectController extends ActionController
{

    /**
     * @Flow\InjectConfiguration(path="errorHandling")
     * @var array
     */
    protected $errorHandling;

    /**
     * @var LinkingService
     * @Flow\Inject
     */
    protected $linkingService;

    /**
     * @Flow\Inject
     * @var LocalizationInterface
     */
    protected $localizationService;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @param NodeInterface $node
     * @param string $identifier
     * @throws Exception
     * @throws \Exception
     * @throws \Neos\Eel\Exception
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function doAction(NodeInterface $node, $identifier = '')
    {
        $q = new FlowQuery([$node]);

        $context = $this->localizationService->getTargetContext($this->request->getHttpRequest());

        $dimensionPath = $this->getUriSegmentForDimensions($context['dimensions']);
        $uri = '/' . $dimensionPath . $this->errorHandling['uri'];
        $statusCode = (int)$this->errorHandling['statusCode'];

        $targetNode = $q->context($context)->find('#' . $identifier)->get(0);
        if ($targetNode !== null) {
            $uri = $this->getUriToNode($targetNode);
            $statusCode = 303;
        }

        $this->redirectToUri($uri, 0, $statusCode);
    }

    /**
     * Create the frontend URL to the node
     *
     * @param NodeInterface $node
     * @return string The URL of the node
     * @throws Exception
     */
    public function getUriToNode(NodeInterface $node)
    {
        $uri = $this->linkingService->createNodeUri(
            new ControllerContext(
                $this->uriBuilder->getRequest(),
                new Response(),
                new Arguments(array()),
                $this->uriBuilder
            ),
            $node,
            $node->getContext()->getRootNode(),
            'html',
            true
        );

        return $uri;
    }

    /**
     * @param array $dimensionsValues
     * @return string
     * @throws \Exception
     */
    protected function getUriSegmentForDimensions(array $dimensionsValues)
    {
        $uriSegment = '';

        foreach ($this->contentDimensionPresetSource->getAllPresets() as $dimensionName => $dimensionPresets) {
            $preset = null;
            if (isset($dimensionsValues[$dimensionName])) {
                $preset = $this->contentDimensionPresetSource->findPresetByDimensionValues($dimensionName, $dimensionsValues[$dimensionName]);
            }
            $defaultPreset = $this->contentDimensionPresetSource->getDefaultPreset($dimensionName);
            if ($preset === null) {
                $preset = $defaultPreset;
            }
            if (!isset($preset['uriSegment'])) {
                throw new \Exception(sprintf('No "uriSegment" configured for content dimension preset "%s" for dimension "%s". Please check the content dimension configuration in Settings.yaml', $preset['identifier'], $dimensionName), 1395824520);
            }
            $uriSegment .= $preset['uriSegment'] . '_';
        }

        return ltrim(trim($uriSegment, '_') . '/', '/');
    }
}
