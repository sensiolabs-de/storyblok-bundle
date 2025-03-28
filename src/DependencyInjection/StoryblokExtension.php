<?php

declare(strict_types=1);

/**
 * This file is part of sensiolabs-de/storyblok-bundle.
 *
 * (c) SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Storyblok\Bundle\DependencyInjection;

use SensioLabs\Storyblok\Api\AssetsApi;
use SensioLabs\Storyblok\Api\AssetsApiInterface;
use SensioLabs\Storyblok\Api\StoryblokClient;
use SensioLabs\Storyblok\Api\StoryblokClientInterface;
use SensioLabs\Storyblok\Bundle\DataCollector\StoryblokCollector;
use SensioLabs\Storyblok\Bundle\Listener\UpdateProfilerListener;
use SensioLabs\Storyblok\Bundle\Webhook\Handler\WebhookHandlerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpClient\TraceableHttpClient;

final class StoryblokExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/../config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->registerForAutoconfiguration(WebhookHandlerInterface::class)
            ->addTag(WebhookHandlerInterface::class);

        $container->setParameter('storyblok_api.base_uri', $config['base_uri']);
        $container->setParameter('storyblok_api.token', $config['token']);
        $container->setParameter('storyblok_api.webhooks.secret', $config['webhook_secret']);
        $container->setParameter('storyblok_api.version', $config['version']);

        if (\array_key_exists('assets_token', $config)) {
            $container->setParameter('storyblok_api.assets_token', $config['assets_token']);
            $this->configureAssetsApi($container);
            $container->setAlias(StoryblokClientInterface::class, StoryblokClient::class);
        }

        if (false === $container->getParameter('kernel.debug')) {
            $container->removeDefinition(StoryblokCollector::class);
            $container->removeDefinition(UpdateProfilerListener::class);
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

    private function configureAssetsApi(ContainerBuilder $container): void
    {
        $client = new Definition(ScopingHttpClient::class);
        $client->setFactory([ScopingHttpClient::class, 'forBaseUri']);
        $client->setArguments([
            '$client' => $container->getDefinition('storyblok.http_client'),
            '$baseUri' => $container->getParameter('storyblok_api.base_uri'),
            '$defaultOptions' => [
                'query' => [
                    'token' => $container->getParameter('storyblok_api.assets_token'),
                ],
            ],
        ]);

        $container->setDefinition('storyblok.assets.scoped_http_client', $client);

        $definition = new Definition(StoryblokClient::class, [
            '$baseUri' => $container->getParameter('storyblok_api.base_uri'),
            '$token' => $container->getParameter('storyblok_api.assets_token'),
        ]);

        $definition->addMethodCall(
            'withHttpClient',
            [$container->getDefinition('storyblok.assets.scoped_http_client')],
        );

        $container->setDefinition('storyblok.assets_client', $definition);

        $container->setDefinition(
            AssetsApi::class,
            new Definition(AssetsApi::class, [
                '$client' => $container->getDefinition('storyblok.assets_client'),
            ]),
        );

        $container->setAlias(AssetsApiInterface::class, AssetsApi::class);
    }
}
