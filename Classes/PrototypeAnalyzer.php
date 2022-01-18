<?php
declare(strict_types=1);

namespace Wwwision\FusionPrototypeAnalyzer;

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Utility;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\FusionService;
use Wwwision\FusionPrototypeAnalyzer\Exception\PrototypeNotFoundInObjectTree;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\FusionObjectTree;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\NodeTypeName;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\PackageKey;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\PrototypeName;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\PrototypeNames;

/**
 * @Flow\Scope("singleton")
 */
final class PrototypeAnalyzer
{

    private FusionService $fusionService;
    private NodeTypeManager $nodeTypeManager;
    private SiteRepository $siteRepository;
    private ContextFactoryInterface $contextFactory;

    public function __construct(FusionService $fusionService, NodeTypeManager $nodeTypeManager, SiteRepository $siteRepository, ContextFactoryInterface $contextFactory)
    {
        $this->fusionService = $fusionService;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->siteRepository = $siteRepository;
        $this->contextFactory = $contextFactory;
    }

    public function getPrototypesUsing(PrototypeName $prototypeNameToSearch, PackageKey $sitePackageKey): PrototypeNames
    {
        $result = PrototypeNames::create();
        $objectTree = $this->fusionObjectTreeForSitePackage($sitePackageKey);
        foreach ($objectTree->prototypeNames() as $prototypeName) {
            if ($this->getNestedPrototypeNames($objectTree, $prototypeName)->has($prototypeNameToSearch)) {
                $result = $result->with($prototypeName);
            }
        }
        return $result;
    }

    public function getNestedPrototypeNamesByNodeType(NodeTypeName $nodeTypeName, PackageKey $sitePackageKey): PrototypeNames
    {
        if (!$this->nodeTypeManager->hasNodeType($nodeTypeName->toString())) {
            throw new \InvalidArgumentException(sprintf('The specified node type "%s" does not exist', $nodeTypeName->toString()), 1642411145);
        }

        $childNodeTypeNames = [];
        $stack = [$nodeTypeName->toString()];
        do {
            $nodeType = $this->nodeTypeManager->getNodeType(array_pop($stack));
            foreach (array_keys((array)$nodeType->getConfiguration('childNodes')) as $childNodeName) {
                $childNodeName = Utility::renderValidNodeName($childNodeName);
                foreach ($this->nodeTypeManager->getSubNodeTypes('Neos.Neos:Content', true) as $innerNodeTypeName => $innerNodeType) {
                    if ($nodeType->allowsGrandchildNodeType($childNodeName, $innerNodeType)) {
                        if (array_key_exists($innerNodeTypeName, $childNodeTypeNames)) {
                            continue;
                        }
                        $childNodeTypeNames[$innerNodeTypeName] = true;
                        $stack[] = $innerNodeTypeName;
                    }
                }
            }
        } while ($stack !== []);
        $fusionObjectTree = $this->fusionObjectTreeForSitePackage($sitePackageKey);
        $prototypeNames = $this->getNestedPrototypeNames($fusionObjectTree, PrototypeName::fromString($nodeTypeName->toString()));
        foreach (array_keys($childNodeTypeNames) as $childNodeTypeName) {
            try {
                $prototypeNamesOfChildNode = $this->getNestedPrototypeNames($fusionObjectTree, PrototypeName::fromString($childNodeTypeName));
            } catch (PrototypeNotFoundInObjectTree $exception) {
                // TODO error?
                continue;
            }
            $prototypeNames = $prototypeNames->merge($prototypeNamesOfChildNode);
        }
        return $prototypeNames;
    }

    // --------------------------------

    private function fusionObjectTreeForSitePackage(PackageKey $sitePackageKey): FusionObjectTree
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $site = $this->siteRepository->findOneBySiteResourcesPackageKey($sitePackageKey->toString());
        if ($site === null) {
            throw new \InvalidArgumentException(sprintf('A site with resource package key "%s" does not exist', $sitePackageKey->toString()), 1642428167);
        }
        /** @var ContentContext $contentContext */
        $contentContext = $this->contextFactory->create(['currentSite' => $site]);
        /** @var TraversableNodeInterface $siteNode */
        $siteNode = $contentContext->getCurrentSiteNode();
        try {
            $fusionObjectTree = $this->fusionService->getMergedFusionObjectTree($siteNode);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed to parse Fusion Object Tree for site package "%s": %s', $sitePackageKey->toString(), $e->getMessage()), 1642409521, $e);
        }
        return FusionObjectTree::fromObjectTreeArray($fusionObjectTree);
    }

    private function getNestedPrototypeNames(FusionObjectTree $objectTree, PrototypeName $rootPrototypeName): PrototypeNames
    {
        $prototypeNames = PrototypeNames::create();
        $stack = [$rootPrototypeName];

        $getNestedPrototypeNames = static function(FusionObjectTree $objectTree, PrototypeName $prototypeName): PrototypeNames {
            $prototypeNames = PrototypeNames::create();
            $prototypeAst = $objectTree->prototypeAst($prototypeName);
            array_walk_recursive($prototypeAst, static function ($value, $key) use (&$prototypeNames) {
                if ($key === '__objectType' && !empty($value)) {
                    $prototypeNames = $prototypeNames->with(PrototypeName::fromString($value));
                }
            });
            return $prototypeNames;
        };

        do {
            $prototypeName = array_pop($stack);
            try {
                $nestedPrototypeNames = $getNestedPrototypeNames($objectTree, $prototypeName);
            } catch (PrototypeNotFoundInObjectTree $e) {
                // TODO error?
                continue;
            }
            foreach ($nestedPrototypeNames as $nestedPrototypeName) {
                if (!$prototypeNames->has($nestedPrototypeName)) {
                    $prototypeNames = $prototypeNames->with($nestedPrototypeName);
                    $stack[] = $nestedPrototypeName;
                }
            }

        } while ($stack !== []);
        return $prototypeNames;
    }

}
