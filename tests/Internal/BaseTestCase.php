<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Tests\Internal;

use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use Orklah\StrictTypes\Plugin;
use Orklah\StrictTypes\Tests\PsalmInternal\TestCase;
use Psalm\Config;
use Psalm\IssueBuffer;

abstract class BaseTestCase extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        //This allow me to ignore almost every issue of Psalm itself
        Config::getInstance()->level = 8;

        //And we enable the plugin
        Config::getInstance()->addPluginClass(Plugin::class);
        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        Config::getInstance()->throw_exception = true;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        self::assertSame(IssueBuffer::getErrorCount(), 0);
    }
}
