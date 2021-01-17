<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Internal;

use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;

abstract class ThrowingTestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->expectException(ShouldNotHappenException::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }
}
