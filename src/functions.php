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
    return array_every($array, fn(mixed $item) => $item instanceof $class);
}

function array_every(array $array, callable $predicate): bool
{
    foreach ($array as $key => $value) {
        if (!call_user_func($predicate, $value, $key)) {
            return false;
        }
    }

    return true;
}

function array_any(array $array, callable $predicate): bool
{
    return !array_every(
        $array,
        fn(mixed $value, string|int $key): bool => !call_user_func($predicate, $value, $key)
    );
}
