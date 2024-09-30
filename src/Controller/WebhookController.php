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

namespace SensioLabs\Storyblok\Bundle\Controller;

use Psr\Log\LoggerInterface;
use SensioLabs\Storyblok\Bundle\Webhook\Event;
use SensioLabs\Storyblok\Bundle\Webhook\WebhookEventHandlerChain;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class WebhookController
{
    public function __construct(
        private LoggerInterface $logger,
        private WebhookEventHandlerChain $handlerChain,
        #[Autowire(param: 'storyblok.webhooks.secret')]
        #[\SensitiveParameter]
        private ?string $webhookSecret = null,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (null === $eventName = $request->headers->get('x-storyblok-topic')) {
            $this->logger->error('Missing "x-storyblok-topic" header.');

            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        if (null === $event = Event::tryFrom($eventName)) {
            $this->logger->error(\sprintf('Unknown event "%s".', $eventName));

            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        if (null !== $this->webhookSecret) {
            if (null === $requestWebhookSignature = $request->headers->get('webhook-signature')) {
                $this->logger->error('Missing "webhook-signature" header.');

                return new Response(status: Response::HTTP_BAD_REQUEST);
            }

            $content = $request->getContent();
            $generatedSignature = hash_hmac('sha1', $content, $this->webhookSecret);

            if ($requestWebhookSignature !== $generatedSignature) {
                $this->logger->error('Invalid "webhook-signature" header.');

                return new Response(status: Response::HTTP_UNAUTHORIZED);
            }
        }

        if (!$this->handlerChain->supports($event)) {
            $this->logger->info(\sprintf('Event "%s" is not supported.', $event->value));

            return new JsonResponse(data: []);
        }

        try {
            $this->handlerChain->handle($event, $request->toArray());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }

        return new JsonResponse(data: []);
    }
}
