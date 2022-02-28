<?php

namespace Zheltikov\Queryf;

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
}
