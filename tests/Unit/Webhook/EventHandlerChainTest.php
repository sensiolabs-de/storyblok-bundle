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

namespace SensioLabs\Storyblok\Bundle\Tests\Unit\Webhook;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SensioLabs\Storyblok\Bundle\Tests\Double\TestHandler;
use SensioLabs\Storyblok\Bundle\Webhook\Event;
use SensioLabs\Storyblok\Bundle\Webhook\Exception\UnsupportedEventException;
use SensioLabs\Storyblok\Bundle\Webhook\Handler\WebhookHandlerInterface;
use SensioLabs\Storyblok\Bundle\Webhook\WebhookEventHandlerChain;

final class EventHandlerChainTest extends TestCase
{
    /**
     * @test
     */
    public function eventIsSupported(): void
    {
        $chain = $this->getChain(new \ArrayIterator([
            new TestHandler(true),
            new TestHandler(false),
            new TestHandler(true),
        ]));

        self::assertTrue($chain->supports(Event::StoryPublished));
    }

    /**
     * @test
     */
    public function eventIsNotSupported(): void
    {
        $chain = $this->getChain(new \ArrayIterator([
            new TestHandler(false),
            new TestHandler(false),
            new TestHandler(false),
        ]));

        self::assertFalse($chain->supports(Event::StoryPublished));
    }

    /**
     * @test
     */
    public function handleThrowsUnsupportedEventExceptionWhenNoHandlersAreRegistered(): void
    {
        $chain = $this->getChain(new \ArrayIterator());

        self::expectException(UnsupportedEventException::class);

        $chain->handle(Event::StoryPublished, []);
    }

    /**
     * @test
     */
    public function handleThrowsUnsupportedEventExceptionWhenNoHandlerIsSupported(): void
    {
        $chain = $this->getChain(new \ArrayIterator([
            new TestHandler(false),
            new TestHandler(false),
        ]));

        self::expectException(UnsupportedEventException::class);

        $chain->handle(Event::StoryPublished, []);
    }

    /**
     * @param iterable<WebhookHandlerInterface> $handlers
     */
    public function getChain(iterable $handlers): WebhookEventHandlerChain
    {
        return new WebhookEventHandlerChain($handlers, new NullLogger());
    }
}
