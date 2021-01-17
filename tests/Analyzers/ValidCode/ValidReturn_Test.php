<?php

namespace Orklah\StrictTypes\Tests\Analyzers\ValidCode;

use Orklah\StrictTypes\Tests\Internal\BaseTestCase;
use Orklah\StrictTypes\Tests\Internal\ValidTestCase;
use Psalm\Context;

class ValidReturn_Test extends ValidTestCase
{
    public function testFunctionReturnLax(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
            function foo(){ return ""; }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testFunctionReturnPhpDoc(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
            /** @return string */
            function foo(){ return ""; }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testFunctionReturnStrict(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
            function foo(): string { return ""; }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testFunctionReturnWrongPhpDoc(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
            /** @return string */
            function foo(){ return 0; }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
