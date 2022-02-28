<?php

namespace Zheltikov\Queryf;

use Countable;
use Iterator;
use JetBrains\PhpStorm\Pure;
use OutOfBoundsException;

class _List implements Countable, Iterator
{
    protected int $current = 0;

    /**
     * @var QueryArgument[]
     */
    protected array $list;

    public function __construct(QueryArgument ...$list)
    {
        $this->list = $list;
    }

    public function append(QueryArgument $value): static
    {
        $this->list[] = $value;
        return $this;
    }

    /**
     * @throws OutOfBoundsException
     */
    public function current(): QueryArgument
    {
        if (!$this->valid()) {
            throw new OutOfBoundsException(sprintf('Undefined List key %d', $this->current));
        }

        return $this->list[$this->current];
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
        return count($this->list);
    }
}
