<?php

namespace Faction\handler\trait;

trait StackableTrait
{
    protected int $maxStack = 300;
    protected int $stack = 1;

    public function canStack(): bool
    {
        return  $this->maxStack > $this->stack;
    }

    public function getMaxStackSize(): int
    {
        return $this->maxStack;
    }

    public function setMaxStackSize(int $stack): void
    {
        $this->maxStack = max($stack, 1);
    }

    public function getStackSize(): int
    {
        return $this->stack;
    }

    public function setStackSize(int $stack): void
    {
        if ($stack > $this->maxStack) {
            $stack = $this->maxStack;
        }

        $this->stack = max($stack, 0);
    }

    public function addStackSize(int $stack): void
    {
        $this->setStackSize($this->stack + $stack);
    }

    public function reduceStackSize(int $stack): void
    {
        $this->setStackSize($this->stack - $stack);
    }
}