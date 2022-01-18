<?php
declare(strict_types=1);

namespace Wwwision\FusionPrototypeAnalyzer\Command;

use Neos\Flow\Cli\CommandController;
use Wwwision\FusionPrototypeAnalyzer\PrototypeAnalyzer;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\AnalyzerResult;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\NodeTypeName;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\PackageKey;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\PrototypeName;
use Wwwision\FusionPrototypeAnalyzer\ValueObject\PrototypeNames;

final class PrototypeCommandController extends CommandController
{
    private PrototypeAnalyzer $analyzer;

    public function __construct(PrototypeAnalyzer $analyzer)
    {
        parent::__construct();
        $this->analyzer = $analyzer;
    }

    /**
     * Find all nested Fusion prototypes used by the specified prototype (recursively)
     *
     * Note: In order to load the correct Fusion Object Tree, the site package should be specified
     *
     * @param string $prototype The fully qualified Fusion prototype to analyze (e.g. "Some.Package:Some.Component")
     * @param string|null $sitePackage The site package to assume to be active. If omitted, the package key is extracted from the specified "prototype"
     * @return void
     */
    public function findNestedCommand(string $prototype, string $sitePackage = null): void
    {
        $prototypeNameToAnalyze = PrototypeName::fromString($prototype);
        $sitePackageKey = $sitePackage !== null ? PackageKey::fromString($sitePackage) : $prototypeNameToAnalyze->packageKey();
        $result = $this->analyzer->getNestedPrototypeNames($prototypeNameToAnalyze, $sitePackageKey);
        $this->renderResult($result, sprintf('The Fusion prototype <b>%s</b> contains <b>%%d</b> other prototype%%s (for site package <b>%s</b>)', $prototypeNameToAnalyze, $sitePackageKey));
    }

    /**
     * Finds all Fusion prototypes that use the specified prototype (recursively)
     *
     * Note: In order to load the correct Fusion Object Tree, the site package should be specified
     *
     * @param string $prototype The fully qualified Fusion prototype to search (e.g. "Some.Package:Some.Component")
     * @param string|null $sitePackage The site package to assume to be active. If omitted, the package key is extracted from the specified "prototype"
     * @return void
     */
    public function findUsagesCommand(string $prototype, string $sitePackage = null): void
    {
        $prototypeNameToSearch = PrototypeName::fromString($prototype);
        $sitePackageKey = $sitePackage !== null ? PackageKey::fromString($sitePackage) : $prototypeNameToSearch->packageKey();
        $result = $this->analyzer->getPrototypesUsing($prototypeNameToSearch, $sitePackageKey);
        $this->renderResult($result, sprintf('The Fusion prototype <b>%s</b> is used by <b>%%d</b> other prototype%%s (for site package <b>%s</b>)', $prototypeNameToSearch, $sitePackageKey));
    }

    /**
     * Finds all¹ Fusion prototypes that are used by the specified node type (recursively)
     *
     * Note: In order to load the correct Fusion Object Tree, the site package should be specified
     *
     * ¹ Only Fusion prototypes with the same name as the node type and all tethered (aka tethered) *content* child nodes are considered!
     *
     * @param string $nodeType The fully qualified NodeType name (e.g. "Some.Package:Some.Node.Type")
     * @param string|null $sitePackage The site package to assume to be active. If omitted, the package key is extracted from the specified node type
     * @return void
     */
    public function findByNodeTypeCommand(string $nodeType, string $sitePackage = null): void
    {
        $nodeTypeToAnalyze = NodeTypeName::fromString($nodeType);
        $sitePackageKey = $sitePackage !== null ? PackageKey::fromString($sitePackage) : $nodeTypeToAnalyze->packageKey();
        $result = $this->analyzer->getNestedPrototypeNamesByNodeType($nodeTypeToAnalyze, $sitePackageKey);
        $this->renderResult($result, sprintf('The Node Type <b>%s</b> uses <b>%%d</b> Fusion prototype%%s (for site package <b>%s</b>)', $nodeTypeToAnalyze, $sitePackageKey));
    }

    // --------------------------

    private function renderResult(AnalyzerResult $result, string $message): void
    {
        $numberOfResults = count($result->prototypeNames);
        if ($numberOfResults === 0) {
            $this->outputLine('<comment>%s</comment>', [sprintf($message, $numberOfResults, 's')]);
        } else {
            $this->outputLine('<success>%s:</success>', [sprintf($message, $numberOfResults, $numberOfResults === 1 ? '' : 's')]);
            $this->outputLine();
            $this->renderPrototypeNames($result->prototypeNames);
        }
        if ($result->hasErrors()) {
            $numberOfErrors = count($result->errorMessages);
            $this->outputLine();
            $this->outputLine('<error>The following %d error%s occurred:</error>', [$numberOfErrors, $numberOfErrors === 1 ? '' : 's']);
            foreach ($result->errorMessages as $errorMessage) {
                $this->outputLine('   <comment>%s</comment>', [$errorMessage]);
            }
        }
    }

    private function renderPrototypeNames(PrototypeNames $prototypeNames): void
    {
        foreach ($prototypeNames->byPackageKey() as $packageKey => $packagePrototypeNames) {
            $this->outputLine('<b>%s (%d)</b>', [$packageKey, count($packagePrototypeNames)]);
            foreach ($packagePrototypeNames as $prototypeName) {
                $this->outputLine('   %s', [$prototypeName->shortName()]);
            }
            $this->outputLine();
        }
    }
}
