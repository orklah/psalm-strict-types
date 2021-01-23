<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\NonStrictCode;

use Orklah\StrictTypes\Tests\Internal\NonStrictTestCase;
use Psalm\Context;

class NonStrictMethodCallTest extends NonStrictTestCase
{
    public function testMethodParamStrict(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function foo(string $a): void {}
            }

            $a = new A();
            $a->foo(1);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodParamStrictInNamespace(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            namespace bar;
            class A{
                public function __construct(){
                    $a = new A();
                    $a->foo(1);
                }
                public function foo(string $a): void {}
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testNamespacedMethodParamStrictInNamespace(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            namespace bar;
            class A{
                public function __construct(){
                    $a = new \bar\A();
                    $a->foo(1);
                }
                public function foo(string $a): void {}
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testMethodParamStrictOptional(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function foo(string $a = ""): void {}
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
                public function foo(string $a = ""): void {}
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
                public function foo(string $a): void {}
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
