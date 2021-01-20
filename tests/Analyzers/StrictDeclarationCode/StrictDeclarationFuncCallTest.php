<?php

namespace Orklah\StrictTypes\Tests\Analyzers\ValidCode;

use Orklah\StrictTypes\Tests\Internal\StrictDeclarationTestCase;
use Psalm\Context;

class StrictDeclarationFuncCallTest extends StrictDeclarationTestCase
{
    public function testCallMapFunction(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            asinh(1.5);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStubbedFunction(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            $a = ["b", "a"];
            sort($a);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testCallMapFunctionInMethod(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function foo(): void {
                    explode("", "");
                }
            }'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
