<?php

namespace Gesdinet\JWTRefreshTokenBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Security\Authenticator\RefreshTokenAuthenticator;
use Gesdinet\JWTRefreshTokenBundle\Security\Provider\RefreshTokenProvider;

class RefreshToken
{
    private $authenticator;
    private $provider;
    private $successHandler;
    private $failureHandler;
    private $refreshTokenManager;

    public function __construct(RefreshTokenAuthenticator $authenticator,
                                RefreshTokenProvider $provider,
                                AuthenticationSuccessHandler $successHandler,
                                AuthenticationFailureHandler $failureHandler,
                                RefreshTokenManagerInterface $refreshTokenManager,
                                $ttl,
                                $providerKey)
    {
        $this->authenticator = $authenticator;
        $this->provider = $provider;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->ttl = $ttl;
        $this->providerKey = $providerKey;
    }

    /**
     * Refresh token
     *
     * @param Request $request
     * @return mixed
     * @throws AuthenticationException
     */
    public function refresh(Request $request)
    {
        try {
            $preAuthenticatedToken = $this->authenticator->authenticateToken(
                $this->authenticator->createToken($request, $this->providerKey),
                $this->provider,
                $this->providerKey
            );
        }catch(AuthenticationException $e) {
            return $this->failureHandler->onAuthenticationFailure($request, $e);
        }

        $refreshToken = $this->refreshTokenManager->get($preAuthenticatedToken->getCredentials());

        if (null === $refreshToken || !$refreshToken->isValid()) {
            return $this->failureHandler->onAuthenticationFailure($request,
                new AuthenticationException(
                    sprintf('Refresh token "%s" is invalid.', $refreshToken)
                )
            );
        }

        $datetime = new \DateTime();
        $datetime->modify("+" . $this->ttl . " seconds");
        $refreshToken->setValid($datetime);

        $this->refreshTokenManager->save($refreshToken);
        return $this->successHandler->onAuthenticationSuccess($request, $preAuthenticatedToken);
    }
}