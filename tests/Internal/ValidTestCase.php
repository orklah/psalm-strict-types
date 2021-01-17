<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Internal;

use Psalm\IssueBuffer;

abstract class ValidTestCase extends BaseTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        self::assertSame(IssueBuffer::getErrorCount(), 0);
    }
}
