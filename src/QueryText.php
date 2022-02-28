<?php

namespace Zheltikov\Queryf;

/**
 * QueryText is a container for query stmt used by the Query.
 */
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

    public function append(string|self $text): static
    {
        $this->query .= is_string($text) ? $text : $text->query;
        return $this;
    }
}
