<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\InvalidCode;

use Orklah\StrictTypes\Tests\Internal\NonStrictTestCase;
use Psalm\Context;

class NonStrictNew_Test extends NonStrictTestCase
{
    public function testMethodParamWrong(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                public function __construct(string $a) {}
            }

            new A(1);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
