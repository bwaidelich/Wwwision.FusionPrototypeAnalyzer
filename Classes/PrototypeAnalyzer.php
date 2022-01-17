<?php
declare(strict_types=1);

namespace Wwwision\FusionPrototypeAnalyzer;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Monocle\Fusion\FusionService;
use Wwwision\FusionPrototypeAnalyzer\Exception\PrototypeNotFoundInObjectTree;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\FusionObjectTree;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\PackageKey;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\PrototypeName;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\PrototypeNames;

/**
 * @Flow\Scope("singleton")
 */
final class PrototypeAnalyzer
{

    private FusionService $fusionService;

    public function __construct(FusionService $fusionService)
    {
        $this->fusionService = $fusionService;
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
