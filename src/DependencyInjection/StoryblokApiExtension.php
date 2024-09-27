<?php

declare(strict_types=1);

namespace SensioLabs\Storyblok\Api\Bundle\DependencyInjection;

use SensioLabs\Storyblok\Api\Bundle\DataCollector\StoryblokCollector;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpClient\TraceableHttpClient;

final class StoryblokApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__).'/../config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('storyblok_api.base_uri', $config['base_uri']);
        $container->setParameter('storyblok_api.token', $config['token']);

        if (false === $container->getParameter('kernel.debug')) {
            $container->removeDefinition(StoryblokCollector::class);
        } else {
            $httpClient = $container->getDefinition('storyblok.http_client');

            $container->setDefinition('storyblok.http_client', new Definition(
                class: TraceableHttpClient::class,
                arguments: [
                    '$client' => $httpClient,
                ],
            ));
        }
    }

    public function getAlias(): string
    {
        return 'storyblok_api';
    }
}