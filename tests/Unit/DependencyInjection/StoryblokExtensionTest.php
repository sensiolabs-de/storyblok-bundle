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

namespace SensioLabs\Storyblok\Bundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SensioLabs\Storyblok\Api\AssetsApi;
use SensioLabs\Storyblok\Api\AssetsApiInterface;
use SensioLabs\Storyblok\Api\StoryblokAssetsClient;
use SensioLabs\Storyblok\Api\StoryblokClientInterface;
use SensioLabs\Storyblok\Bundle\DataCollector\StoryblokCollector;
use SensioLabs\Storyblok\Bundle\DependencyInjection\StoryblokExtension;
use SensioLabs\Storyblok\Bundle\Listener\UpdateProfilerListener;
use SensioLabs\Storyblok\Bundle\Tests\Util\FakerTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\TraceableHttpClient;

final class StoryblokExtensionTest extends TestCase
{
    use FakerTrait;

    /**
     * @test
     */
    public function loadWillSetParameters(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', $faker->boolean());

        $config = [
            ['base_uri' => $baseUri = $faker->url()],
            ['token' => $token = $faker->uuid()],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertSame($baseUri, $builder->getParameter('storyblok_api.base_uri'));
        self::assertSame($token, $builder->getParameter('storyblok_api.token'));
    }

    /**
     * @test
     */
    public function loadWithoutKernelDebugWillRemoveDefinitions(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', false);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertFalse($builder->hasDefinition(StoryblokCollector::class));
        self::assertFalse($builder->hasDefinition(UpdateProfilerListener::class));
    }

    /**
     * @test
     */
    public function loadWithKernelDebugWillReplaceHttpClientWithTracableHttpClient(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', true);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertTrue($builder->hasDefinition('storyblok.http_client'));

        $definition = $builder->getDefinition('storyblok.http_client');

        self::assertSame(TraceableHttpClient::class, $definition->getClass());
    }

    /**
     * @test
     */
    public function loadWithoutAssetsToken(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', true);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertFalse($builder->hasDefinition(StoryblokAssetsClient::class));
        self::assertFalse($builder->hasAlias(AssetsApiInterface::class));
        self::assertFalse($builder->hasAlias(StoryblokClientInterface::class));
        self::assertFalse($builder->hasDefinition(AssetsApi::class));
        self::assertFalse($builder->hasParameter('storyblok_api.assets_token'));
    }

    /**
     * @test
     */
    public function loadWithAssetsToken(): void
    {
        $faker = self::faker();

        $extension = new StoryblokExtension();
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.debug', true);

        $config = [
            ['base_uri' => $faker->url()],
            ['token' => $faker->uuid()],
            ['assets_token' => $token = $faker->uuid()],
        ];

        $extension->load(
            $config,
            $builder,
        );

        self::assertTrue($builder->hasDefinition(StoryblokAssetsClient::class));
        self::assertTrue($builder->hasAlias(AssetsApiInterface::class));
        self::assertTrue($builder->hasAlias(StoryblokClientInterface::class));
        self::assertTrue($builder->hasDefinition(AssetsApi::class));
        self::assertTrue($builder->hasParameter('storyblok_api.assets_token'));
        self::assertSame($token, $builder->getParameter('storyblok_api.assets_token'));
    }
}
