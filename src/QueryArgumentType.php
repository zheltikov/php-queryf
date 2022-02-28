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
        return match (true) {
            is_string($value) => self::String,
            is_bool($value) => self::Bool,
            is_null($value) => self::Null,
            is_double($value) => self::Double,
            is_int($value) => self::Int,
            $value instanceof Query => self::Query,
            $value instanceof TwoTuple => self::TwoTuple,
            $value instanceof ThreeTuple => self::ThreeTuple,
            $value instanceof _List => self::List,
            $value instanceof PairList => self::PairList,
            default => throw new InvalidArgumentException(
                'Could not find a matching QueryArgumentType for value with type ' . get_debug_type($value),
            ),
        };
    }
}
