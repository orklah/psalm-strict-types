<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\NonStrictCode;

use Orklah\StrictTypes\Tests\Internal\BadTypeFromSignatureTestCase;
use Psalm\Context;

class BadTypeFromSignatureNew_Test extends BadTypeFromSignatureTestCase
{
    public function testNew(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function __construct(string $a) {}
            }

            new A(1);'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testNewExpr(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function __construct(string $a) {}
            }
            $a = A::class;
            new $a(1);'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testNewExprNamespace(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            namespace foo;
            class A{
                public function __construct(string $a) {
                    $a = A::class;
                    new $a(1);
                }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testNewOtherNamespace(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            namespace foo{
                class A{
                    public function __construct(string $a) {}
                }
            }

            namespace foo2{
                use foo\A;
                class B{
                    function scope(): void {
                        new A(1);
                    }
                }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testNewSelf(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class B{
                function __construct(string $a){}
                function scope(): void {
                    new self(1);
                }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }
}
