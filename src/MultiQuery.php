<?php

namespace Zheltikov\Queryf;

use InvalidArgumentException;
use mysqli;

/**
 * Wraps many queries and holds a buffer that contains the rendered multi query
 * from all the subqueries.
 */
class MultiQuery
{
    protected string $unsafe_multi_query;
    protected string $rendered_multi_query;

    /**
     * @param Query[] $queries
     */
    public function __construct(protected array $queries = [])
    {
    }

    /**
     * Construct an unsafe multi query.
     */
    public static function unsafe(string|QueryText $multi_query): static
    {
        $that = new static();
        $that->unsafe_multi_query = is_string($multi_query)
            ? $multi_query : $multi_query->getQuery();
        return $that;
    }

    public function renderQuery(?mysqli $conn): string
    {
        if ($this->unsafe_multi_query !== '') {
            return $this->unsafe_multi_query;
        }

        $this->rendered_multi_query = Query::renderMultiQuery($conn, $this->queries);
        return $this->rendered_multi_query;
    }

    public function getQuery(int $index): Query
    {
        if ($index < count($this->queries)) {
            return $this->queries[$index];
        }

        throw new InvalidArgumentException();
    }

    /**
     * @return Query[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
}
