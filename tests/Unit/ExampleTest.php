<?php

namespace Tests\Unit;

use Spark\Testing\TestCase;
use Spark\Support\Str;

final class ExampleTest extends TestCase
{
    public function test_strings_can_be_converted_to_snake_case(): void
    {
        $this->assertSame('hello_world', Str::snake('HelloWorld'));
    }
}
