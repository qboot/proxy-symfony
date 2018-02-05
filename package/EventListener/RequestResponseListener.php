<?php

namespace RedirectionIO\Client\SymfonyBundle\EventListener;

use RedirectionIO\Client\Sdk\Client;
use RedirectionIO\Client\Sdk\HttpMessage\Request;
use RedirectionIO\Client\Sdk\HttpMessage\Response;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class RequestResponseListener
{
    /**
     * RedirectionIO Client.
     *
     * @var Client;
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return false;
        }

        $this->findRedirect($event->getRequest());

        $response = $event->getRequest()->get('redirectionio_response');

        if (null === $response) {
            return false;
        }

        $event->setResponse(new SymfonyRedirectResponse($response->getLocation(), $response->getStatusCode()));

        return true;
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $request = $this->createSdkRequest($event->getRequest());
        $response = $event->getRequest()->get('redirectionio_response');

        if (null === $response) {
            $response = new Response($event->getResponse()->getStatusCode());
        }

        return $this->client->log($request, $response);
    }

    private function findRedirect(SymfonyRequest $symfonyRequest)
    {
        $response = $this->client->findRedirect(
            $this->createSdkRequest($symfonyRequest)
        );

        $symfonyRequest->attributes->set('redirectionio_response', $response);
    }

    private function createSdkRequest(SymfonyRequest $symfonyRequest)
    {
        return new Request(
            $symfonyRequest->getHttpHost(),
            $symfonyRequest->getRequestUri(),
            $symfonyRequest->headers->get('User-Agent'),
            $symfonyRequest->headers->get('Referer')
        );
    }
}
