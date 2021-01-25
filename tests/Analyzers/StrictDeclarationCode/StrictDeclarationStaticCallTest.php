<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\StrictDeclarationCode;

use Orklah\StrictTypes\Tests\Internal\StrictDeclarationTestCase;
use Psalm\Context;

class StrictDeclarationStaticCallTest extends StrictDeclarationTestCase
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
                    A::test(1);
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
            A::test(1);'
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
                    parent::test(1);
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
                    self::test(1);
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
                    parent::test(1);
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
                    $b::test(1);
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
