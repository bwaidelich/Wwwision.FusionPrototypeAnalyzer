<?php
declare(strict_types=1);

namespace Wwwision\FusionPrototypeAnalyzer\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class PackageKey implements \Serializable
{
    private string $value;

    private static $instances = [];

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    private static function constant(string $value): self
    {
        return self::$instances[$value] ?? self::$instances[$value] = new self($value);
    }

    public static function fromString(string $value): self
    {
        return self::constant($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function __clone()
    {
        throw new \RuntimeException('Cloning not supported');
    }

    public function serialize(): ?string
    {
        throw new \RuntimeException('Serialization not supported');
    }

    public function unserialize($data): void
    {
        throw new \RuntimeException('Deserialization not supported');
    }
}
