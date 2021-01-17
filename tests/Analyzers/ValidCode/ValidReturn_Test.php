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
            __CLASS__.__METHOD__.'.php',
            '<?php
            function foo(){ return ""; }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testFunctionReturnPhpDoc(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            /** @return string */
            function foo(){ return ""; }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testFunctionReturnStrict(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            function foo(): string { return ""; }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testFunctionReturnWrongPhpDoc(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            /** @return string */
            function foo(){ return 0; }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodReturnLax(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function foo(){ return ""; }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodReturnPhpDoc(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                /** @return string */
                public function foo(){ return ""; }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodReturnStrict(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function foo(): string { return ""; }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodReturnWrongPhpDoc(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                /** @return string */
                public function foo(){ return 0; }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
