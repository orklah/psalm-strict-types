<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Internal;

use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Psalm\Exception\CodeException;

abstract class ThrowingTestCase /*extends BaseTestCase*/ //this doesn't work yet
{
    public function setUp(): void
    {
        parent::setUp();

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('ShouldNotHappenException');
    }
}
