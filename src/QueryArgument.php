<?php

namespace Zheltikov\Queryf;

use InvalidArgumentException;
use Zheltikov\Queryf\QueryArgumentType as Type;

class QueryArgument
{
    protected mixed $value;
    protected Type $type;

    protected function __construct()
    {
    }

    // -------------------------------------------------------------------------

    public function typeName(): Type
    {
        return $this->type;
    }

    // -------------------------------------------------------------------------

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
