<?php

namespace Zheltikov\Queryf;

class ThreeTuple
{
    public function __construct(
        protected string $first,
        protected string $second,
        protected string $third,
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

    public function getThird(): string
    {
        return $this->third;
    }
}
