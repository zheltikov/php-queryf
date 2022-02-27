<?php

namespace Zheltikov\Queryf;

class QueryText
{
    protected string $query;

    public function __construct(string $query)
    {
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
