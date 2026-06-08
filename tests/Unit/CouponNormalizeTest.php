<?php

namespace Tests\Unit;

use App\Legacy\LegacyBridge;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CouponNormalizeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        LegacyBridge::boot();
    }

    #[Test]
    public function coupon_code_is_normalized_to_uppercase(): void
    {
        $this->assertSame('SUMMER10', \lh_coupon_normalize_code(' summer10 '));
    }
}
