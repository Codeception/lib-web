<?php

namespace Tests\Codeception\Constraint;

use Codeception\Constraint\Page;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    public function testPassesIfPageContainsString(): void
    {
        $constraint = new Page('text', 'uri');
        $this->assertNull($constraint->evaluate('long text string'));
    }

    public function testFailsIfPageDoesntContainString(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(<<<EOT
        Failed asserting that on page uri
        --> other string
        --> contains "text".
        EOT
        );

        $constraint = new Page('text', 'uri');
        $this->assertNull($constraint->evaluate('other string'));
    }
}
