<?php

namespace Ikerlin\Alipay;

use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

use Ikerlin\Alipay\Aop\AopClient;
use Ikerlin\Alipay\Aop\Request\AlipaySystemOauthTokenRequest;
use Ikerlin\Alipay\Aop\Request\AlipayUserUserinfoShareRequest;
use Illuminate\Support\Facades\Storage;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'ALIPAY';

    /**
     * {@inheritdoc}.
     */
    protected $scopes = ['auth_userinfo'];

    /**
     * @var string
     */
    protected $userId;

    /**
     * {@inheritdoc}.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://openauth.alipay.com/oauth2/publicAppAuthorize.htm', $state);
    }

    /**
     * {@inheritdoc}.
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        $query = http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);

        return $url . '?' . $query;
    }

    /**
     * {@inheritdoc}.
     */
    protected function getCodeFields($state = null)
    {
        return [
            'app_id' => $this->clientId, 'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'state' => $state,
        ];
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenUrl()
    {
        return 'https://openapi.alipay.com/gateway.do';
    }

    /**
     * {@inheritdoc}.
     */
    protected function getUserByToken($token)
    {
        if (in_array('auth_base', $this->scopes)) {
            $user = ['alipay_user_id' => $this->userId];
        } else {
            $alipayUserUserinfoShareRequest = new AlipayUserUserinfoShareRequest();
            $result = $this->executeAopClient($alipayUserUserinfoShareRequest, $token);

            $user = objectToArray($result->alipay_user_userinfo_share_response);
        }

        return $user;
    }

    /**
     * {@inheritdoc}.
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['alipay_user_id'],
            'nickname' => isset($user['nick_name']) ? $user['nick_name'] : null,
            'avatar' => isset($user['avatar']) ? $user['avatar'] : null,
            'name' => null,
            'email' => null,
        ]);
    }
    
    /**
     * Get the code from the request.
     *
     * @return string
     */
    protected function getCode()
    {
        return $this->request->input('auth_code');
    }

    /**
     * {@inheritdoc}.
     */
    public function getAccessTokenResponse($code)
    {
        $AlipaySystemOauthTokenRequest = new AlipaySystemOauthTokenRequest ();
        $AlipaySystemOauthTokenRequest->setCode($code);
        $AlipaySystemOauthTokenRequest->setGrantType("authorization_code");
        $result = $this->executeAopClient($AlipaySystemOauthTokenRequest, $code);

        $this->credentialsResponseBody = objectToArray($result->alipay_system_oauth_token_response);
        $this->userId = $this->credentialsResponseBody['user_id'];

        return $this->credentialsResponseBody;
    }

    /**
     * 执行AopClient请求
     * @param $request
     * @param $token
     * @return bool|mixed|\SimpleXMLElement
     */
    private function executeAopClient($request, $token)
    {
        $aop = new AopClient ();
        $aop->gatewayUrl = $this->getTokenUrl();
        $aop->appId = $this->clientId;

//        $aop->rsaPrivateKey = Storage::disk('alikey')->get('privateKey.rsa');
//        $aop->alipayrsaPublicKey = Storage::disk('alikey')->get('publicKey.rsa');
        $aop->rsaPrivateKeyFilePath = storage_path(config('services.alipay.privateKeyFilePath'));
        $aop->alipayPublicKey = storage_path(config('services.alipay.publicKeyFilePath'));

        $aop->signType = 'RSA2';
        $aop->apiVersion = '1.0';
        $result = $aop->execute($request, $token);

        return $result;
    }
}

/**
 * 将对象转换为多维数组
 *
 **/
function objectToArray($d)
{
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }

    if (is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return array_map(__FUNCTION__, $d);
    } else {
        // Return array
        return $d;
    }
}
