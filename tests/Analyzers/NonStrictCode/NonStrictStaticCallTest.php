<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\ValidCode;

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
}
