<?php
declare(strict_types=1);

namespace Wwwision\FusionPrototypeAnalyzer\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class PrototypeNames implements \IteratorAggregate, \Countable
{
    /**
     * @var PrototypeName[] indexed by their string value
     */
    private array $prototypeNames;

    private function __construct(array $prototypeNames)
    {
        $this->prototypeNames = $prototypeNames;
    }

    public static function fromStringArray(array $prototypeNames): self
    {
        $result = [];
        foreach ($prototypeNames as $prototypeName) {
            $result[$prototypeName] = PrototypeName::fromString($prototypeName);
        }
        return new self($result);
    }

    public static function create(): self
    {
        return new self([]);
    }

    public function with(PrototypeName $prototypeName): self
    {
        if ($this->has($prototypeName)) {
            return $this;
        }
        $prototypeNames = $this->prototypeNames;
        $prototypeNames[$prototypeName->toString()] = $prototypeName;
        return new self($prototypeNames);
    }

    public function has(PrototypeName $prototypeName): bool
    {
        return array_key_exists($prototypeName->toString(), $this->prototypeNames);
    }

    /**
     * @return iterable<PrototypeName>
     */
    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->prototypeNames);
    }

    /**
     * @return array<PrototypeNames>
     */
    public function byPackageKey(): array
    {
        $result = [];
        foreach ($this->prototypeNames as $prototypeName) {
            $packageKey = $prototypeName->packageKey()->toString();
            if (!array_key_exists($packageKey, $result)) {
                $result[$packageKey] = self::create();
            }
            $result[$packageKey] = $result[$packageKey]->with($prototypeName);
        }
        return $result;
    }

    public function count(): int
    {
        return count($this->prototypeNames);
    }
}
