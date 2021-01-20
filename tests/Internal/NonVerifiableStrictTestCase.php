<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Internal;

use Psalm\Exception\CodeException;

abstract class NonVerifiableStrictTestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('NonVerifiableStrictUsageIssue');
    }
}
