<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\StrictDeclarationCode;

use Orklah\StrictTypes\Tests\Internal\StrictDeclarationTestCase;
use Psalm\Context;

class StrictDeclarationNew_Test extends StrictDeclarationTestCase
{
    public function testNew(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function __construct(string $a) {}
            }

            new A("");'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testNewExpr(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function __construct(string $a) {}
            }
            $a = A::class;
            new $a("");'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testNewExprNamespace(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            namespace foo;
            class A{
                public function __construct(string $a) {
                    $a = A::class;
                    new $a("");
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
