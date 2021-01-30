<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\StrictDeclarationCode;

use Orklah\StrictTypes\Tests\Internal\StrictDeclarationTestCase;
use Psalm\Context;

class StrictDeclarationFuncCallTest extends StrictDeclarationTestCase
{
    public function testCallMapFunction(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            asinh(1.5);'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testStubbedFunction(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            $a = ["b", "a"];
            sort($a);'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testCallMapFunctionInMethod(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            class A{
                public function foo(): void {
                    explode("", "");
                }
            }'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testFunctionVariadics(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            function foo(string $_a, string ...$_b): void{

            }
            foo("");
            foo("", "");
            foo("", "", "");'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }
}
