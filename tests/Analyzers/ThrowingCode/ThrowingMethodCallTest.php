<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\ThrowingCode;

use Orklah\StrictTypes\Tests\Internal\ThrowingTestCase;
use Psalm\Context;

class ThrowingMethodCallTest extends ThrowingTestCase
{
    /**
     * This needs to be fixed, when in a namespace and outside the class, no node provider holds the variable?!?
     */
    public function testMethodParamStrictInNamespace(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            namespace bar;
            class A{
                public function foo(string $a): void {}
            }

            $a = new A();
            $a->foo("");'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    /**
     * This needs to be fixed, when in a namespace and outside the class, no node provider holds the variable?!?
     */
    public function testNamespacedMethodParamStrictInNamespace(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            namespace bar;
            class A{
                public function foo(string $a): void {}
            }

            $a = new \bar\A();
            $a->foo("");'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    /**
     * This needs to be fixed, it implies being able to calculate the target of static
     */
    public function testStaticParamStatic(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public static function test(int $a){}
            }

            class B extends A{
                public static function test(int $a){
                    static::test(1);
                }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    /**
     * This needs to be fixed, somehow, the use of the variable is problematic. The method is found, but the argument can't be found in node provider
     */
    public function testMethodParamExpr(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function __construct(){
                    $method = "foo";
                    $this->$method("");
                }
                public function foo(string $a): void {}
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    /**
     * This needs to be fixed, somehow, the use of the variable is problematic. The node provider don't return anything
     */
    public function testStaticAssignFromOutsideClass(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public static string $a;
            }

            $a = new A();
            $a::$b = "1";'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    /**
     * This needs to be fixed
     */
    public function testMethodCallNonLiteral(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A
            {
                public function __construct()
                {
                    $this->{"foo"}(1);
                }

                public function foo(string $a): void
                {
                }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }
}
