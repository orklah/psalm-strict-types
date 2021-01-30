<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\StrictDeclarationCode;

use Orklah\StrictTypes\Tests\Internal\StrictDeclarationTestCase;
use Psalm\Context;

class StrictDeclarationMethodCallTest extends StrictDeclarationTestCase
{
    public function testMethodParamStrict(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function foo(string $a) {}
            }

            $a = new A();
            $a->foo("");'
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
                    $a->foo("");
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
                    $a->foo("");
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
            $a->foo("");'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testMethodParamFromClass(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function __construct() {
                    $this->foo("");
                }
                public function foo(string $a): void {}
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
                    $this->a->foo("");
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
                public string $a = "";
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
                public static string $a = "";
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
                public static string $a = "";
                public function __construct(){
                    $this->foo(static::$a);
                }
                public function foo(string $a): void {}
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }
}
