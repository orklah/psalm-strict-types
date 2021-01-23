<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\ValidCode;

use Orklah\StrictTypes\Tests\Internal\StrictDeclarationTestCase;
use Psalm\Context;

class StrictDeclarationAssignTest extends StrictDeclarationTestCase
{
    public function testAssignFromClass(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public string $a;

                public function __construct(){
                    $this->a = "1";
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
            $a->a = "1";'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStaticAssignFromClass(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public string $a;

                public function __construct(){
                    self::$a = "1";
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStaticAssignFromOutsideClass(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public string $a;
            }

            $a = new A();
            $a::$a = "1";'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
