<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\NonStrictCode;

use Orklah\StrictTypes\Tests\Internal\NonStrictTestCase;
use Psalm\Context;

class NonStrictStaticCallTest extends NonStrictTestCase
{
    public function testStaticParamStrictInsideClass(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public static function test(int $a){}
            }

            class B{
                public static function test(int $a){
                    A::test("");
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStaticParamStrictGlobalScope(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public static function test(int $a){}
            }
            A::test("");'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStaticParamStrictParent(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public static function test(int $a){}
            }

            class B extends A{
                public static function test(int $a){
                    parent::test("");
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStaticParamSelf(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public static function test(int $a){}
            }

            class B extends A{
                public static function test(int $a){
                    self::test("");
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStaticParamParent(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public static function test(int $a){}
            }

            class B extends A{
                public static function test(int $a){
                    parent::test("");
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStaticParamClassString(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class B{
                public static function test(int $a){
                    $b = B::class;
                    $b::test("");
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
