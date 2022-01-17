<?php
declare(strict_types=1);

namespace Wwwision\FusionPrototypeAnalyzer;

use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Utility;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Service\NodeTypeSchemaBuilder;
use Sitegeist\Monocle\Fusion\FusionService;
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

    public function __construct(FusionService $fusionService, NodeTypeManager $nodeTypeManager)
    {
        $this->fusionService = $fusionService;
        $this->nodeTypeManager = $nodeTypeManager;
    }

    public function getPrototypesUsing(PrototypeName $prototypeNameToSearch, PackageKey $sitePackageKey): PrototypeNames
    {
        $result = PrototypeNames::create();
        $objectTree = $this->fusionObjectTreeForSitePackage($sitePackageKey);
        foreach ($objectTree->prototypeNames() as $prototypeName) {
            if ($this->getPrototypeNames($objectTree, $prototypeName)->has($prototypeNameToSearch)) {
                $result = $result->with($prototypeName);
            }
        }
        return $result;
    }

    public function getNestedPrototypeNames(PrototypeName $prototypeName, PackageKey $sitePackageKey): PrototypeNames
    {
        return $this->getPrototypeNames($this->fusionObjectTreeForSitePackage($sitePackageKey), $prototypeName);
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
        $prototypeNames = $this->getPrototypeNames($fusionObjectTree, PrototypeName::fromString($nodeTypeName->toString()));
        foreach (array_keys($childNodeTypeNames) as $childNodeTypeName) {
            try {
                $prototypeNamesOfChildNode = $this->getPrototypeNames($fusionObjectTree, PrototypeName::fromString($childNodeTypeName));
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
        try {
            return FusionObjectTree::fromObjectTreeArray($this->fusionService->getMergedFusionObjectTreeForSitePackage($sitePackageKey->toString()));
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed to parse Fusion Object Tree for site package "%s": %s', $sitePackageKey->toString(), $e->getMessage()), 1642409521, $e);
        }
    }

    private function getPrototypeNames(FusionObjectTree $objectTree, PrototypeName $rootPrototypeName): PrototypeNames
    {
        $rootPrototypeAst = $objectTree->prototypeAst($rootPrototypeName);
        $prototypeNames = PrototypeNames::create();
        $stack = $this->fusionService->getAnatomicalPrototypeTreeFromAstExcerpt($rootPrototypeAst);
        do {
            $item = array_pop($stack);
            foreach ($item['children'] ?? [] as $child) {
                $stack[] = $child;
            }
            if (!isset($item['prototypeName'])) {
                // TODO exception?
                continue;
            }
            $prototypeName = PrototypeName::fromString($item['prototypeName']);
            if ($prototypeNames->has($prototypeName)) {
                continue;
            }
            $prototypeNames = $prototypeNames->with($prototypeName);
            try {
                $prototypeAst = $objectTree->prototypeAst($prototypeName);
            } catch (PrototypeNotFoundInObjectTree $e) {
                // TODO error
                continue;
            }
            foreach ($this->fusionService->getAnatomicalPrototypeTreeFromAstExcerpt($prototypeAst) as $subItem) {
                $stack[] = $subItem;
            }

        } while ($stack !== []);
        return $prototypeNames;
    }

}
