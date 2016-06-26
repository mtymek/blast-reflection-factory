<?php

namespace Blast\Test\ReflectionFactory\Asset;

class BarService
{
    public function __construct(FooService $fooService, QuxService $quxService)
    {
    }
}
