<?php

namespace Orklah\StrictTypes\Tests\Analyzers\InvalidCode;

use Orklah\StrictTypes\Tests\Internal\InvalidTestCase;
use Psalm\Context;

class InvalidReturn_Test extends InvalidTestCase
{
    public function testFunctionReturnStrict(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
            function foo(): string { return 0; }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
