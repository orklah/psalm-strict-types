<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Analyzers\ValidCode;

use Orklah\StrictTypes\Tests\Internal\StrictDeclarationTestCase;
use Psalm\Context;

class StrictDeclarationNew_Test extends StrictDeclarationTestCase
{
    public function testConstructParamPhpDocType(): void
    {
        $this->addFile(
            __CLASS__.__METHOD__.'.php',
            '<?php
            class A{
                /** @param string $a */
                public function __construct($a) {}
            }

            new A(1);'
        );

        $this->analyzeFile(__CLASS__.__METHOD__.'.php', new Context());
    }
}
