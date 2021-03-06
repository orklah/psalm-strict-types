<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\NonStrictCode;

use Orklah\StrictTypes\Tests\Internal\BadTypeFromSignatureTestCase;
use Psalm\Context;

class BadTypeFromSignatureMethodCallTest extends BadTypeFromSignatureTestCase
{
    public function testMethodParamStrict(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function foo(string $a): void {}
            }

            $a = new A();
            $a->foo(1);'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodParamStrictInNamespace(): void
    {
        $this->addFile(
            __METHOD__.'.php',
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

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testNamespacedMethodParamStrictInNamespace(): void
    {
        $this->addFile(
            __METHOD__.'.php',
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

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodParamStrictOptional(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function foo(string $a = ""): void {}
            }

            $a = new A();
            $a->foo(1);'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodParamFromClass(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function __construct(){
                    $this->foo(1);
                }
                public function foo(string $a = ""): void {}
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodParamThroughProperty(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public A $a;
                public function __construct(){
                    $this->a->foo(1);
                }
                public function foo(string $a): void {}
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodParamPropertyThis(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public int $a = 1;
                public function __construct(){
                    $this->foo($this->a);
                }
                public function foo(string $a): void {}
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodParamPropertySelf(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public static int $a = 1;
                public function __construct(){
                    $this->foo(self::$a);
                }
                public function foo(string $a): void {}
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodParamPropertyStatic(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public static int $a = 1;
                public function __construct(){
                    $this->foo(static::$a);
                }
                public function foo(string $a): void {}
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodCompositeNamespace(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            namespace A\B\C{
                class F{
                    function scope(){
                    \A\B\D\E::f(1);
                    }
                }
            }

            namespace A\B\D{
                class E{
                    public static function f(string $a){

                    }
                }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodVariadics(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function __construct(){
                    $this->f("", "", 1);
                }
                public function f(string $a, string ...$variadics){

                }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodUnpacking(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function __construct(){
                    $a = ["", "", 1];
                    $this->f(...$a);
                }
                public function f(string $a, string $b, string $c){

                }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }
}
