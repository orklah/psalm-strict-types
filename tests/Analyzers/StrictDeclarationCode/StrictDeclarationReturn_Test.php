<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\StrictDeclarationCode;

use Orklah\StrictTypes\Tests\Internal\StrictDeclarationTestCase;
use Psalm\Context;

class StrictDeclarationReturn_Test extends StrictDeclarationTestCase
{
    public function testFunctionReturnLax(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            function foo(){ return ""; }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testFunctionReturnPhpDoc(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            /** @return string */
            function foo(){ return ""; }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testFunctionReturnStrict(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            function foo(): string { return ""; }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testFunctionReturnWrongPhpDoc(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            /** @return string */
            function foo(){ return 0; }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodReturnLax(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function foo(){ return ""; }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodReturnPhpDoc(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                /** @return string */
                public function foo(){ return ""; }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodReturnStrict(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function foo(): string { return ""; }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodReturnWrongPhpDoc(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                /** @return string */
                public function foo(){ return 0; }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }
}
