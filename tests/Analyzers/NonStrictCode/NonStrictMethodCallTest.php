<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\InvalidCode;

use Orklah\StrictTypes\Tests\Internal\NonStrictTestCase;
use Psalm\Context;

class NonStrictMethodCallTest extends NonStrictTestCase
{
    public function testMethodParamPhpDocType(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function foo(string $a) {}
            }

            $a = new A();
            $a->foo(1);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodParamPhpDocTypeOptional(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function foo(string $a = "") {}
            }

            $a = new A();
            $a->foo(1);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodParamFromClass(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function __construct(){
                    $this->foo(1);
                }
                public function foo(string $a = "") {}
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodParamThroughProperty(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public A $a;
                public function __construct(){
                    $this->a->foo(1);
                }
                public function foo(string $a) {}
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
