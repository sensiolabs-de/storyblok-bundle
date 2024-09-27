<?php

declare(strict_types=1);

namespace SensioLabs\Storyblok\Api\Bundle;

use SensioLabs\Storyblok\Api\Bundle\DependencyInjection\StoryblokApiExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class StoryblokApiBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->registerExtension(new StoryblokApiExtension());
    }
}
