<?php

namespace Tests\Unit;

use App\Support\LocalNetworkHost;
use Illuminate\Http\Request;
use Tests\TestCase;

class LocalNetworkHostTest extends TestCase
{
    public function test_private_ipv4_is_flexible_local_host(): void
    {
        $this->assertTrue(LocalNetworkHost::isFlexibleLocalHost('192.168.18.47'));
        $this->assertTrue(LocalNetworkHost::isFlexibleLocalHost('172.16.16.90'));
        $this->assertTrue(LocalNetworkHost::isFlexibleLocalHost('10.0.0.5'));
    }

    public function test_public_ipv4_is_not_flexible_local_host(): void
    {
        $this->assertFalse(LocalNetworkHost::isFlexibleLocalHost('8.8.8.8'));
    }

    public function test_sanctum_host_variants_include_common_ports(): void
    {
        $request = Request::create('http://192.168.18.47/login', 'GET');

        $variants = LocalNetworkHost::sanctumHostVariants($request);

        $this->assertContains('192.168.18.47', $variants);
        $this->assertContains('192.168.18.47:80', $variants);
    }
}
