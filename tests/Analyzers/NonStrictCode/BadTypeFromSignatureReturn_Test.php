<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\NonStrictCode;

use Orklah\StrictTypes\Tests\Internal\BadTypeFromSignatureTestCase;
use Psalm\Context;

class BadTypeFromSignatureReturn_Test extends BadTypeFromSignatureTestCase
{
    public function testFunctionReturnStrict(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            function foo(): string { return 0; }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodReturnStrict(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function foo(): string { return 0; }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }
}
