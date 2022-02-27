<?php

namespace Zheltikov\Queryf;

/**
 * @param mixed ...$params
 */
function queryf(string $query, ...$params): string
{
    return (new Query(
        $query,
        array_map(
            fn($p) => new QueryArgument($p),
            $params
        )
    ))->render(null);
}
