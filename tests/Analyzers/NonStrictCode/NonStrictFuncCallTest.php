<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\NonStrictCode;

use Orklah\StrictTypes\Tests\Internal\NonStrictTestCase;
use Psalm\Context;

class NonStrictFuncCallTest extends NonStrictTestCase
{
    public function testCallMapFunction(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            asinh(new stdClass);'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }

    public function testStubbedFunction(): void
    {
        $this->addFile(
            __METHOD__.'.php',
            '<?php
            $a = 7;
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
                    explode(0, 0);
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
            foo("", "", 0);'
        );

        $this->analyzeFile(__METHOD__.'.php', new Context());
    }
}
