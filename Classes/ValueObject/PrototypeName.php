<?php
declare(strict_types=1);

namespace Wwwision\FusionPrototypeAnalyzer\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class PrototypeName implements \Serializable
{
    private string $value;

    private static array $instances = [];

    private function __construct(string $value)
    {
        if (substr_count($value, ':') !== 1) {
            throw new \InvalidArgumentException(sprintf('Fully qualified Fusion prototype name (like "Some.Package:Some.Prototype") expected, given: "%s"', $value), 1642409220);
        }
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

    public function packageKey(): PackageKey
    {
        return PackageKey::fromString(strtok($this->value, ':'));
    }

    public function shortName(): string
    {
        return substr($this->value, strpos($this->value, ':') + 1);
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
