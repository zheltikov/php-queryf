<?php

namespace Zheltikov\Queryf;

class TwoTuple
{
    public function __construct(
        protected string $first,
        protected string $second,
    ) {
    }

    public function getFirst(): string
    {
        return $this->first;
    }

    public function getSecond(): string
    {
        return $this->second;
    }
}
