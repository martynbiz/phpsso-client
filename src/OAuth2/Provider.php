<?php
/**
 * This file is part of the league/oauth2-client library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Alex Bilbie <hello@alexbilbie.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @link http://thephpleague.com/oauth2-client/ Documentation
 * @link https://packagist.org/packages/league/oauth2-client Packagist
 * @link https://github.com/thephpleague/oauth2-client GitHub
 */

namespace SSO\MWAuth\OAuth2;

// use InvalidArgumentException;
// use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
// use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
// use Psr\Http\Message\ResponseInterface;

/**
 * And extended provider for MWAuth to allow additional additional resource
 * owner actions (e.g. update, delete)
 */
class Provider extends \League\OAuth2\Client\Provider\GenericProvider //AbstractProvider
{
    /**
     * @var string HTTP method used to fetch access tokens.
     */
    const METHOD_PUT = 'PUT';

    /**
     * @var string HTTP method used to fetch access tokens.
     */
    const METHOD_DELETE = 'DELETE';

    // use BearerAuthorizationTrait;
    //
    // /**
    //  * @var string
    //  */
    // private $urlAuthorize;
    //
    // /**
    //  * @var string
    //  */
    // private $urlAccessToken;
    //
    // /**
    //  * @var string
    //  */
    // private $urlResourceOwnerDetails;
    //
    // /**
    //  * @var string
    //  */
    // private $accessTokenMethod;
    //
    // /**
    //  * @var string
    //  */
    // private $accessTokenResourceOwnerId;
    //
    // /**
    //  * @var array|null
    //  */
    // private $scopes = null;
    //
    // /**
    //  * @var string
    //  */
    // private $scopeSeparator;
    //
    // /**
    //  * @var string
    //  */
    // private $responseError = 'error';
    //
    // /**
    //  * @var string
    //  */
    // private $responseCode;
    //
    // /**
    //  * @var string
    //  */
    // private $responseResourceOwnerId = 'id';
    //
    // /**
    //  * @param array $options
    //  * @param array $collaborators
    //  */
    // public function __construct(array $options = [], array $collaborators = [])
    // {
    //     $this->assertRequiredOptions($options);
    //
    //     $possible   = $this->getConfigurableOptions();
    //     $configured = array_intersect_key($options, array_flip($possible));
    //
    //     foreach ($configured as $key => $value) {
    //         $this->$key = $value;
    //     }
    //
    //     // Remove all options that are only used locally
    //     $options = array_diff_key($options, $configured);
    //
    //     parent::__construct($options, $collaborators);
    // }
    //
    // /**
    //  * Returns all options that can be configured.
    //  *
    //  * @return array
    //  */
    // protected function getConfigurableOptions()
    // {
    //     return array_merge($this->getRequiredOptions(), [
    //         'accessTokenMethod',
    //         'accessTokenResourceOwnerId',
    //         'scopeSeparator',
    //         'responseError',
    //         'responseCode',
    //         'responseResourceOwnerId',
    //         'scopes',
    //     ]);
    // }
    //
    // /**
    //  * Returns all options that are required.
    //  *
    //  * @return array
    //  */
    // protected function getRequiredOptions()
    // {
    //     return [
    //         'urlAuthorize',
    //         'urlAccessToken',
    //         'urlResourceOwnerDetails',
    //     ];
    // }
    //
    // /**
    //  * Verifies that all required options have been passed.
    //  *
    //  * @param  array $options
    //  * @return void
    //  * @throws InvalidArgumentException
    //  */
    // private function assertRequiredOptions(array $options)
    // {
    //     $missing = array_diff_key(array_flip($this->getRequiredOptions()), $options);
    //
    //     if (!empty($missing)) {
    //         throw new InvalidArgumentException(
    //             'Required options not defined: ' . implode(', ', array_keys($missing))
    //         );
    //     }
    // }
    //
    // /**
    //  * @inheritdoc
    //  */
    // public function getBaseAuthorizationUrl()
    // {
    //     return $this->urlAuthorize;
    // }
    //
    // /**
    //  * @inheritdoc
    //  */
    // public function getBaseAccessTokenUrl(array $params)
    // {
    //     return $this->urlAccessToken;
    // }
    //
    // /**
    //  * @inheritdoc
    //  */
    // public function getResourceOwnerDetailsUrl(AccessToken $token)
    // {
    //     return $this->urlResourceOwnerDetails;
    // }
    //
    // /**
    //  * @inheritdoc
    //  */
    // public function getDefaultScopes()
    // {
    //     return $this->scopes;
    // }
    //
    // /**
    //  * @inheritdoc
    //  */
    // protected function getAccessTokenMethod()
    // {
    //     return $this->accessTokenMethod ?: parent::getAccessTokenMethod();
    // }
    //
    // /**
    //  * @inheritdoc
    //  */
    // protected function getAccessTokenResourceOwnerId()
    // {
    //     return $this->accessTokenResourceOwnerId ?: parent::getAccessTokenResourceOwnerId();
    // }
    //
    // /**
    //  * @inheritdoc
    //  */
    // protected function getScopeSeparator()
    // {
    //     return $this->scopeSeparator ?: parent::getScopeSeparator();
    // }
    //
    // /**
    //  * @inheritdoc
    //  */
    // protected function checkResponse(ResponseInterface $response, $data)
    // {
    //     if (!empty($data[$this->responseError])) {
    //         $error = $data[$this->responseError];
    //         $code  = $this->responseCode ? $data[$this->responseCode] : 0;
    //         throw new IdentityProviderException($error, $code, $data);
    //     }
    // }
    //
    // /**
    //  * @inheritdoc
    //  */
    // protected function createResourceOwner(array $response, AccessToken $token)
    // {
    //     return new GenericResourceOwner($response, $this->responseResourceOwnerId);
    // }

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    public function updateResourceOwner(AccessToken $token, $params)
    {
        // we use the same url for get, update, delete; just the method is different
        $url = $this->getResourceOwnerDetailsUrl($token);
        $method = self::METHOD_PUT;
        $options = array(
            'headers' => ['content-type' => 'application/x-www-form-urlencoded'],
            'body' => http_build_query($params),
        );

        // build request/ get response
        $request = $this->getAuthenticatedRequest($method, $url, $token, $options);

        return $this->getResponse($request);
    }

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    public function deleteResourceOwner(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);
        $request = $this->getAuthenticatedRequest(self::METHOD_DELETE, $url, $token);
        $response = $this->getResponse($request);

        // return $this->createResourceOwner($response, $token);
    }
}
