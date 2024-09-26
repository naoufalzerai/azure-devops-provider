<?php

namespace SocialiteProviders\Devops;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'DEVOPS';


    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['vso.build_execute', 'vso.code_write', 'vso.identity', 'vso.pipelineresources_use', 'vso.threads_full', 'vso.work_write'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $register_url = "https://app.vssps.visualstudio.com/oauth2/authorize?client_id=".$this->getConfig('client_id')."&response_type=Assertion&state=User1&scope=vso.build_execute%20vso.code_write%20vso.graph%20vso.identity%20vso.pipelineresources_use%20vso.threads_full%20vso.work_write&redirect_uri=".$this->getConfig('redirect');
        return $register_url;
    }

    /**
     * Return the logout endpoint with an optional post_logout_redirect_uri query parameter.
     *
     * @param string|null $redirectUri The URI to redirect to after logout, if provided.
     *                                 If not provided, no post_logout_redirect_uri parameter will be included.
     *
     * @return string The logout endpoint URL.
     */
    public function getLogoutUrl(?string $redirectUri = null)
    {
        $logoutUrl = $this->getBaseUrl().'/oauth2/logout';

        return $redirectUri === null ?
            $logoutUrl :
            $logoutUrl.'?'.http_build_query(['post_logout_redirect_uri' => $redirectUri], '', '&', $this->encodingType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/oauth2/token';
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $url = "https://dev.azure.com/".$this->getConfig('organisation')."/_apis/ConnectionData";
        $response = $this->getHttpClient()->get($url, [
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);
        $f = json_decode((string) $response->getBody(), true);
        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        exit(dd($user));
        return (new User())->setRaw($user)->map([
            'id'            => $user['id'],
            'nickname'      => null,
            'name'          => $user['displayName'],
            'email'         => $user['userPrincipalName'],
            'principalName' => $user['userPrincipalName'],
            'mail'          => $user['mail'],
            'avatar'        => null,
        ]);
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS     => ['Content-Type' => 'application/x-www-form-urlencoded'],
            RequestOptions::FORM_PARAMS => [
                "client_assertion_type"=>"urn:ietf:params:oauth:client-assertion-type:jwt-bearer",
                "client_assertion"=>$this->getConfig('secret'),
                "grant_type"=>"urn:ietf:params:oauth:grant-type:jwt-bearer",
                "assertion"=>$code,
                "redirect_uri"=>$this->getConfig('redirect'),
            ],
        ]);
        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return 'https://app.vssps.visualstudio.com';
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return [ 'secret','organisation'];
    }
}
