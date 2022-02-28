<?php

namespace Zheltikov\Queryf;

class Pair
{
    public function __construct(
        protected string $first,
        protected QueryArgument $second,
    ) {
    }

    public function getFirst(): string
    {
        return $this->first;
    }

    public function getFirstArgument(): QueryArgument
    {
        return QueryArgument::newString($this->first);
    }

    public function getSecond(): QueryArgument
    {
        return $this->second;
    }
}
