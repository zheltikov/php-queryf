<?php

namespace Zheltikov\Queryf;

use InvalidArgumentException;

enum QueryArgumentType: string
{
    case String = 'string';
    case Query = 'query';
    case PairList = 'pair_list';
    case Bool = 'bool';
    case Null = 'null';
    case List = 'list';
    case Double = 'double';
    case Int = 'int';
    case TwoTuple = 'two_tuple';
    case ThreeTuple = 'three_tuple';

    /**
     * @throws InvalidArgumentException
     */
    public static function fromDynamic(mixed $value): self
    {
        if (is_string($value)) {
            return self::String;
        } elseif (is_bool($value)) {
            return self::Bool;
        } elseif (is_null($value)) {
            return self::Null;
        } elseif (is_double($value)) {
            return self::Double;
        } elseif (is_int($value)) {
            return self::Int;
        } elseif ($value instanceof Query) {
            return self::Query;
        } elseif (is_array($value)) {
            if (array_is_list($value)) {
                if (
                    count($value) === 2
                    && array_every($value, is_string(...))
                ) {
                    return self::TwoTuple;
                }

                if (
                    count($value) === 3
                    && array_every($value, is_string(...))
                ) {
                    return self::ThreeTuple;
                }

                if (array_every($value, fn(mixed $item) => $item instanceof QueryArgument)) {
                    return self::List;
                }
            } elseif (array_every(
                $value,
                fn(mixed $item, string|int $key) => is_string($key) && $item instanceof QueryArgument
            )) {
                return self::PairList;
            }
        }

        throw new InvalidArgumentException(
            'Could not find a matching QueryArgumentType for value with type ' . get_debug_type($value),
        );
    }
}
