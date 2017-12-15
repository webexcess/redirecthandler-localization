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
use WebExcess\RedirectHandler\Localization\Service\LocalizationInterface;

class RedirectController extends ActionController
{

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
     * @param NodeInterface $node
     * @param string $identifier
     * @throws Exception
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function doAction(NodeInterface $node, $identifier = '')
    {
        $q = new FlowQuery([$node]);

        $context = $this->localizationService->getTargetContext($this->request->getHttpRequest());

        $targetNode = $q->context($context)->find('#' . $identifier)->get(0);
        $uri = $this->getUriToNode($targetNode);

        $this->redirectToUri($uri);
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

}
