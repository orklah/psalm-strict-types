<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\ValidCode;

use Orklah\StrictTypes\Tests\Internal\StrictDeclarationTestCase;
use Psalm\Context;

class StrictDeclarationMethodCallTest extends StrictDeclarationTestCase
{
    public function testFunctionParamPhpDocType(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                /** @param string $a */
                public function foo($a) {}
            }

            $a = new A();
            $a->foo(1);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
