<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Internal;

use Psalm\IssueBuffer;

abstract class InvalidTestCase extends BaseTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        self::assertTrue(IssueBuffer::getErrorCount() > 0);
    }
}
