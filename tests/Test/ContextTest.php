<?php

declare(strict_types=1);

namespace Test;

use Phluxor\WebSocket\Context;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    public function testGetValue(): void
    {
        $ctx = new Context([
            'key' => ['value']
        ]);
        $this->assertSame(['value'], $ctx->getValue('key'));
    }

    public function testGetNullValue(): void
    {
        $ctx = new Context([
            'key' => ['value']
        ]);

        $this->assertSame(null, $ctx->getValue('other'));
    }

    public function testGetValues(): void
    {
        $ctx = new Context([
            'key' => ['value']
        ]);

        $this->assertSame([
            'key' => ['value']
        ], $ctx->getValues());
    }


    public function testWithValue(): void
    {
        $ctx = new Context([
            'key' => ['value']
        ]);

        $this->assertSame(['value'], $ctx->getValue('key'));

        $ctx2 = $ctx->withValue('new', 'another')->withValue('key', ['value2']);

        $this->assertSame(['value'], $ctx->getValue('key'));
        $this->assertSame(null, $ctx->getValue('new'));

        $this->assertSame(['value2'], $ctx2->getValue('key'));
        $this->assertSame('another', $ctx2->getValue('new'));
    }
}
