<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Internal;

use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Psalm\Exception\CodeException;
//investigate assertStateIsUnchanged(); https://twitter.com/fschmengler/status/1362726039441596418
abstract class ThrowingTestCase /*extends BaseTestCase*/ //this doesn't work yet
{
    public function setUp(): void
    {
        parent::setUp();

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('ShouldNotHappenException');
    }
}
