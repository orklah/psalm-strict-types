<?php

namespace Orklah\StrictTypes\Tests\Analyzers\InvalidCode;

use Orklah\StrictTypes\Tests\Internal\InvalidTestCase;
use Psalm\Context;

class InvalidReturn_Test extends InvalidTestCase
{
    public function testFunctionReturnStrict(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            function foo(): string { return 0; }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodReturnStrict(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function foo(): string { return 0; }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
