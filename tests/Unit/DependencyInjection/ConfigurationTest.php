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

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use SensioLabs\Storyblok\Bundle\DependencyInjection\Configuration;
use SensioLabs\Storyblok\Bundle\Tests\Util\FakerTrait;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;
    use FakerTrait;

    /**
     * @test
     */
    public function values(): void
    {
        $faker = self::faker();
        $url = $faker->url();
        $token = $faker->uuid();
        $secret = $faker->uuid();

        self::assertProcessedConfigurationEquals([
            ['base_uri' => $url],
            ['token' => $token],
            ['webhook_secret' => $secret],
        ], [
            'base_uri' => $url,
            'token' => $token,
            'webhook_secret' => $secret,
        ]);
    }

    /**
     * @test
     */
    public function webhookSecretDefault(): void
    {
        $faker = self::faker();
        $url = $faker->url();
        $token = $faker->uuid();

        self::assertProcessedConfigurationEquals([
            ['base_uri' => $url],
            ['token' => $token],
        ], [
            'base_uri' => $url,
            'token' => $token,
            'webhook_secret' => null,
        ]);
    }

    /**
     * @test
     */
    public function configBaseUriMustExist(): void
    {
        $faker = self::faker();
        $token = $faker->uuid();

        self::assertConfigurationIsInvalid(
            [['token' => $token]],
            'The child config "base_uri" under "storyblok" must be configured.',
        );
    }

    /**
     * @test
     */
    public function configTokenMustExist(): void
    {
        $faker = self::faker();
        $url = $faker->url();

        self::assertConfigurationIsInvalid(
            [['base_uri' => $url]],
            'The child config "token" under "storyblok" must be configured.',
        );
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
