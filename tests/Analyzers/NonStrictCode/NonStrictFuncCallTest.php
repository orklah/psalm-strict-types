<?php

namespace Orklah\StrictTypes\Tests\Analyzers\ValidCode;

use Orklah\StrictTypes\Tests\Internal\NonStrictTestCase;
use Psalm\Context;

class NonStrictFuncCallTest extends NonStrictTestCase
{
    public function testCallMapFunction(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            asinh(new stdClass);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }

    public function testStubbedFunction(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            $a = 7;
            sort($a);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
