<?php
namespace Orklah\StrictTypes;

use Orklah\StrictTypes\Hooks\StrictTypesAnalyzer;
use SimpleXMLElement;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

class Plugin implements PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(RegistrationInterface $psalm, ?SimpleXMLElement $config = null): void
    {
        if(class_exists(StrictTypesAnalyzer::class)){
            $psalm->registerHooksFromClass(StrictTypesAnalyzer::class);
        }
    }
}
