<?php
declare(strict_types=1);

namespace Wwwision\FusionPrototypeAnalyzer\ValueObject;

use Neos\Flow\Annotations as Flow;
use Wwwision\FusionPrototypeAnalyzer\Exception\PrototypeNotFoundInObjectTree;

/**
 * @Flow\Proxy(false)
 */
final class FusionObjectTree
{
    private array $rawObjectTree;

    private function __construct(array $rawObjectTree)
    {
        $this->rawObjectTree = $rawObjectTree;
    }

    public static function fromObjectTreeArray(array $rawObjectTree): self
    {
        return new self($rawObjectTree);
    }

    public function prototypeAst(PrototypeName $prototypeName): array
    {
        if (!isset($this->rawObjectTree['__prototypes'][$prototypeName->toString()])) {
            throw new PrototypeNotFoundInObjectTree(sprintf('Fusion prototype "%s" does not appear in the object tree for the specified site package', $prototypeName), 1642154582);
        }
        return $this->rawObjectTree['__prototypes'][$prototypeName->toString()];
    }

    public function prototypeNames(): PrototypeNames
    {
        return PrototypeNames::fromStringArray(array_keys($this->rawObjectTree['__prototypes']));
    }

}
