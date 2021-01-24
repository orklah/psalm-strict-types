<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\NonStrictCode;

use Orklah\StrictTypes\Tests\Internal\NonStrictTestCase;
use Psalm\Context;

class NonStrictAssignTest extends NonStrictTestCase
{
    public function testAssignFromClass(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public string $a;

                public function __construct(){
                    $this->a = 1;
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testAssignFromOutsideClass(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public string $a;
            }

            $a = new A();
            $a->a = 1;'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStaticAssignFromClass(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public static string $a;

                public function __construct(){
                    self::$a = 1;
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
