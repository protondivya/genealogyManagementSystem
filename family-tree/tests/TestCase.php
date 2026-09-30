<?php

namespace Tests;

use App\Models\Family;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Family::forgetCurrent();

        parent::tearDown();
    }
}
