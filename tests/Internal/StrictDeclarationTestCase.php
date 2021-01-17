<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Internal;

use Psalm\Exception\CodeException;

abstract class StrictDeclarationTestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('StrictDeclarationToAddIssue');
    }
}
