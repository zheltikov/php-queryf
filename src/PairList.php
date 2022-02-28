<?php

namespace Zheltikov\Queryf;

use Countable;
use Iterator;
use JetBrains\PhpStorm\Pure;
use OutOfBoundsException;

class PairList implements Countable, Iterator
{
    protected int $current = 0;

    /**
     * @var Pair[]
     */
    protected array $pair_list;

    public function __construct(Pair ...$pair_list)
    {
        $this->pair_list = $pair_list;
    }

    public function append(Pair $pair): static
    {
        $this->pair_list[] = $pair;
        return $this;
    }

    /**
     * @throws OutOfBoundsException
     */
    public function current(): Pair
    {
        if (!$this->valid()) {
            throw new OutOfBoundsException(sprintf('Undefined PairList key %d', $this->current));
        }

        return $this->pair_list[$this->current];
    }

    public function next(): void
    {
        $this->current++;
    }

    public function key(): int
    {
        return $this->current;
    }

    #[Pure]
    public function valid(): bool
    {
        return $this->current < $this->count();
    }

    public function rewind(): void
    {
        $this->current = 0;
    }

    public function count(): int
    {
        return count($this->pair_list);
    }
}
