<?php

namespace App\EventListener;

use App\Repository\AuthRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTStatusListener
{
    private AuthRepository $authRepository;
    private RequestStack $requestStack;

    public function __construct(AuthRepository $authRepository, RequestStack $requestStack)
    {
        $this->authRepository = $authRepository;
        $this->requestStack = $requestStack;
    }

    public function onJWTDecoded(JWTDecodedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            $event->markAsInvalid();
            return;
        }

        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
            $event->markAsInvalid();
            return;
        }

        $token = substr($authHeader, 7);
        $auth = $this->authRepository->findOneBy(['token' => $token]);

        if (!$auth || $auth->isRevoked()) {
            $event->markAsInvalid();
        }
    }
}
