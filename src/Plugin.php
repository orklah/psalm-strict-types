<?php declare(strict_types=1);
namespace Orklah\StrictTypes;

use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use SimpleXMLElement;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        if(class_exists(StrictTypesHooks::class)){
            $registration->registerHooksFromClass(StrictTypesHooks::class);
        }
    }
}
