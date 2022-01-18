<?php
declare(strict_types=1);

namespace Wwwision\FusionPrototypeAnalyzer\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class AnalyzerResult
{

    public PrototypeNames $prototypeNames;
    public array $errorMessages;

    public function __construct(PrototypeNames $prototypeNames, array $errorMessages)
    {
        $this->prototypeNames = $prototypeNames;
        $this->errorMessages = $errorMessages;
    }

    public function hasErrors(): bool
    {
        return $this->errorMessages !== [];
    }

}
