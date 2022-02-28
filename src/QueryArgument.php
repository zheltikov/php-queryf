<?php

namespace Zheltikov\Queryf;

use InvalidArgumentException;
use Zheltikov\Queryf\QueryArgumentType as Type;

use function Zheltikov\Invariant\invariant;

class QueryArgument
{
    protected mixed $value;
    protected Type $type;

    protected function __construct()
    {
    }

    // -------------------------------------------------------------------------

    public function asString(): string
    {
        return match (true) {
            $this->isDouble(),
            $this->isInt(),
            $this->isString() => (string) $this->value,

            $this->isBool() => $this->value ? 'TRUE' : 'FALSE',

            default => throw new InvalidArgumentException(
                sprintf(
                    'Only allowed type conversions are Int, Double, Bool and String: type found: %s (%s)',
                    $this->type->name,
                    $this->type->value,
                ),
            ),
        };
    }

    public function getDouble(): float
    {
        invariant(
            $this->type === Type::Double,
            'Expected QueryArgument type to be Type::Double',
        );

        return $this->value;
    }

    public function getInt(): int
    {
        invariant(
            $this->type === Type::Int,
            'Expected QueryArgument type to be Type::Int',
        );

        return $this->value;
    }

    public function getBool(): bool
    {
        invariant(
            $this->type === Type::Bool,
            'Expected QueryArgument type to be Type::Bool',
        );

        return $this->value;
    }

    public function getQuery(): Query
    {
        invariant(
            $this->type === Type::Query,
            'Expected QueryArgument type to be Type::Query',
        );

        return $this->value;
    }

    public function getString(): string
    {
        invariant(
            $this->type === Type::String,
            'Expected QueryArgument type to be Type::String',
        );

        return $this->value;
    }

    /**
     * @return QueryArgument[]
     */
    public function getList(): array
    {
        invariant(
            $this->type === Type::List,
            'Expected QueryArgument type to be Type::List',
        );

        return $this->value;
    }

    public function getPairs(): array
    {
        invariant(
            $this->type === Type::PairList,
            'Expected QueryArgument type to be Type::PairList',
        );

        return $this->value;
    }

    public function getTwoTuple(): array
    {
        invariant(
            $this->type === Type::TwoTuple,
            'Expected QueryArgument type to be Type::TwoTuple',
        );

        return $this->value;
    }

    public function getThreeTuple(): array
    {
        invariant(
            $this->type === Type::ThreeTuple,
            'Expected QueryArgument type to be Type::ThreeTuple',
        );

        return $this->value;
    }

    public function typeName(): Type
    {
        return $this->type;
    }

    // -------------------------------------------------------------------------

    /**
     * Since we already have callsites that use dynamic, we are keeping the
     * support, but internally we unpack them.
     * This factory method will throw exception if the dynamic isn't acceptable
     * Creating this as a factory method has two benefits: one is it will prevent
     * accidentally adding more callsites, secondly it is easily bgs-able.
     * Also makes it explicit this might throw whereas the other constructors
     * might not.
     *
     * @throws InvalidArgumentException
     */
    public static function fromDynamic(Type $type, mixed ...$args): static
    {
        /** @var callable $factory */
        $factory = match ($type) {
            Type::String => static::newString(...),
            Type::Query => static::newQuery(...),
            Type::PairList => count($args) === 0
                ? static::new(...)
                : static::newPairList(...),
            Type::Bool => static::newBool(...),
            Type::Null => static::newNull(...),
            Type::List => static::newList(...),
            Type::Double => static::newDouble(...),
            Type::Int => static::newInt(...),
            Type::TwoTuple => static::newTwoTuple(...),
            Type::ThreeTuple => static::newThreeTuple(...),
            default => throw new InvalidArgumentException(
                'Unknown QueryArgumentType with name ' . $type->name . ' and value ' . $type->value,
            ),
        };

        return $factory(...$args);
    }

    public static function newString(string $value): static
    {
        $that = new static();
        $that->type = Type::String;
        $that->value = $value;
        return $that;
    }

    public static function newQuery(Query $value): static
    {
        $that = new static();
        $that->type = Type::Query;
        $that->value = $value;
        return $that;
    }

    public static function newPairList(string $param1, self $param2): static
    {
        $that = new static();
        $that->type = Type::PairList;
        $that->value = [
            [$param1, $param2],
        ];
        return $that;
    }

    public static function new(): static
    {
        $that = new static();
        $that->type = Type::PairList;
        $that->value = [];
        return $that;
    }

    public static function newBool(bool $value): static
    {
        $that = new static();
        $that->type = Type::Bool;
        $that->value = $value;
        return $that;
    }

    public static function newNull(): static
    {
        $that = new static();
        $that->type = Type::Null;
        $that->value = null;
        return $that;
    }

    public static function newList(self ...$list): static
    {
        $that = new static();
        $that->type = Type::List;
        $that->value = $list;
        return $that;
    }

    public static function newDouble(float $value): static
    {
        $that = new static();
        $that->type = Type::Double;
        $that->value = $value;
        return $that;
    }

    public static function newInt(int $value): static
    {
        $that = new static();
        $that->type = Type::Int;
        $that->value = $value;
        return $that;
    }

    public static function newTwoTuple(string $param1, string $param2): static
    {
        $that = new static();
        $that->type = Type::TwoTuple;
        $that->value = [$param1, $param2];
        return $that;
    }

    public static function newThreeTuple(string $param1, string $param2, string $param3): static
    {
        $that = new static();
        $that->type = Type::ThreeTuple;
        $that->value = [$param1, $param2, $param3];
        return $that;
    }

    // -------------------------------------------------------------------------

    public function isString(): bool
    {
        return $this->type === Type::String;
    }

    public function isQuery(): bool
    {
        return $this->type === Type::Query;
    }

    public function isPairList(): bool
    {
        return $this->type === Type::PairList;
    }

    public function isBool(): bool
    {
        return $this->type === Type::Bool;
    }

    public function isNull(): bool
    {
        return $this->type === Type::Null;
    }

    public function isList(): bool
    {
        return $this->type === Type::List;
    }

    public function isDouble(): bool
    {
        return $this->type === Type::Double;
    }

    public function isInt(): bool
    {
        return $this->type === Type::Int;
    }

    public function isTwoTuple(): bool
    {
        return $this->type === Type::TwoTuple;
    }

    public function isThreeTuple(): bool
    {
        return $this->type === Type::ThreeTuple;
    }
}
