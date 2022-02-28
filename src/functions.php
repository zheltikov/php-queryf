<?php

namespace Zheltikov\Queryf;

use mysqli;

/**
 * @param mixed ...$params
 */
function queryf(?mysqli $connection, string $query, ...$params): string
{
    return (new Query(
        $query,
        array_map(
            fn($p) => QueryArgument::fromDynamic(
                QueryArgumentType::fromDynamic($p),
                $p
            ),
            $params
        )
    ))->render($connection);
}
