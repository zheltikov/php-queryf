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
            fn($p) => QueryArgument::fromDynamic(
                QueryArgumentType::fromDynamic($p),
                $p
            ),
            $params
        )
    ))->render(null);
}

/**
 * @param class-string $class
 */
function isVectorOf(array $array, string $class): bool
{
    foreach ($array as $item) {
        if (!($item instanceof $class)) {
            return false;
        }
    }

    return true;
}
