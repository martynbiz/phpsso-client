<?php
namespace SSO\MWAuth;

use SSO\MWAuth\Storage\StorageInterface;
use SSO\MWAuth\Exception\MissingUrl as MissingUrlException;
use SSO\MWAuth\OAuth2\Provider;
use League\OAuth2\Client\Provider\GenericResourceOwner;

/**
 * This is a framework agnostic class for accessing MWAuth session attributes. It is
 * designed to work with SSO/MWAuth authenticated sessions to acheive single sign-on
 * for applications on the same domain (e.g. *.example.com/* )
 * @category SSO
 * @package MWAuthClient
 * @author Martyn Bissett
 */
class Client
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var Session
     */
    protected $storage;

    public function __construct(StorageInterface $storage, $options=array())
    {
        $this->storage = $storage;
        $this->options = $options;
    }

    /**
    * Check whether a user is authenticated or not
    * @return boolean
    */
    public function isAuthenticated()
    {
        // simple check to see if we have sso session variables
        // uses $options['namespace'] which must be consistant across all apps (inc sso server app)

        return (!empty($this->getAttributes()));
    }

    /**
    * Get attributes from the session if they exist
    * @return array
    */
    public function getAttributes()
    {
        $contents = $this->storage->getContents();

        return (isset($contents['attributes'])) ? $contents['attributes'] : array();
    }

    /**
    * Essentially logging a user out of all applications - used mainly when we
    * do a forceLogin. Will delete sso/mwauth session variables.
    * @return void
    */
    public function clearAttributes()
    {
        $this->storage->emptyContents();
    }

    /**
     * Get the login URL, the same URL is used for GET and POST requests
     * @param array $params e.g. GET params returnTo=http://...
     * @return string
     */
    public function getLoginUrl($params=array())
    {
        if (! array_key_exists('server_url', $this->options))
            throw new MissingUrlException('Server URL not set in client');

        $loginUrl = $this->options['server_url'] . '/session';
        return $this->buildUrlWithParams($loginUrl, $params);
    }

    /**
     * Get the login URL, the same URL is used for GET and POST requests
     * @param array $params e.g. GET params returnTo=http://...
     * @return string
     */
    public function getFacebookLoginUrl($params=array())
    {
        if (! array_key_exists('server_url', $this->options))
            throw new MissingUrlException('Server URL not set in client');

        $fbLoginUrl = $this->options['server_url'] . '/session/facebook';
        return $this->buildUrlWithParams($fbLoginUrl, $params);
    }

    /**
     * Get the logout URL, the same URL is used for GET and POST requests
     * @param array $params e.g. GET params returnTo=http://...
     * @return string
     */
    public function getLogoutUrl($params=array())
    {
        if (! array_key_exists('server_url', $this->options))
            throw new MissingUrlException('Server URL not set in client');

        $logoutUrl = $this->options['server_url'] . '/session';
        return $this->buildUrlWithParams($logoutUrl, $params);
    }

    /**
     * Get the register URL, the same URL is used for GET and POST requests
     * @param array $params e.g. GET params returnTo=http://...
     * @return string
     */
    public function getRegisterUrl($params=array())
    {
        if (! array_key_exists('server_url', $this->options))
            throw new MissingUrlException('Server URL not set in client');

        $registerUrl = $this->options['server_url'] . '/accounts';
        return $this->buildUrlWithParams($registerUrl, $params);
    }

    /**
     * Get the register URL, the same URL is used for GET and POST requests
     * @param array $params e.g. GET params returnTo=http://...
     * @return string
     */
    public function getForgotPasswordUrl($params=array())
    {
        if (! array_key_exists('server_url', $this->options))
            throw new MissingUrlException('Server URL not set in client');

        $forgotPasswordUrl = $this->options['server_url'] . '/accounts/resetpassword';
        return $this->buildUrlWithParams($forgotPasswordUrl, $params);
    }

    /**
     * Fetches the current URL, allows us to set default returnTo without having to
     * request this every call
     * @return string
     */
    public static function getCurrentUrl() { //($includePortNumber=false) {

        // get the protocol and domain e.g. http://mydomain.com
        $url  = @( isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] == 'on' ) ? 'https://'.$_SERVER["SERVER_NAME"] :  'http://'.$_SERVER["SERVER_NAME"];

        // include the port number e.g. mydomain.com:80
        // if($includePortNumber)
           $url .= ( (int) $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";

        // get the path to the resource e.g. /path/to/resource?params=should-be-included-too
        $url .= $_SERVER["REQUEST_URI"];

        return $url;
    }

    /**
     * Will initiate the login process
     * @param array $params Eg. returnTo
     */
    public function login($params=array())
    {
        // if user is already authenticated then return
        if ($this->isAuthenticated()) return;

        // setup provider from options (e.g. client_id)
        $provider = new Provider( array(
            'clientId'                => $this->options['client_id'],
            'clientSecret'            => $this->options['client_secret'],
            'redirectUri'             => $params['returnTo'],
            'urlAuthorize'            => $this->options['server_url'] . '/oauth/authorize',
            'urlAccessToken'          => $this->options['server_url'] . '/oauth/token',
            'urlResourceOwnerDetails' => $this->options['server_url'] . '/api/account',
        ) );

        // $this->update(array(), array());

        // If we don't have an authorization code then get one
        if (! isset($_GET['code'])) {

            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl();

            // Get the state generated for you and store it to the session.
            // TODO use namespace?
            $_SESSION['oauth2state'] = $provider->getState();

            // Redirect the user to the authorization URL.
            header('Location: ' . $authorizationUrl);
            exit;

        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

            // TODO use namespace?
            unset($_SESSION['oauth2state']);
            exit('Invalid state');

        } else {

            // here we have a $_GET['code'], use it wisely

            try {

                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);

                // store the access token for later use too. we might use this
                // for updating the account
                $this->storage['access_token'] = $accessToken;

                // Using the access token, we may look up details about the
                // resource owner and write to storage
                $resourceOwner = $provider->getResourceOwner($accessToken);
                $this->storage['attributes'] = $resourceOwner->toArray();

            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

                // Failed to get the access token or user details.
                exit($e->getMessage());

            }

        }
    }

    /**
     * This is called and will check if the user requires redirecting to the auth
     * app to login if auth_token is set.
     * @param array $params Eg. returnTo
     */
    public function passiveLogin($params=array()) // checkLogin? w/ passive
    {
        // only trigger remember me code if not authenticated, we don't need this
        // at all if they are already signed in (duh)
        if (! $this->isAuthenticated()) {

            // check if we have checked the login already, without this we may
            // end up with an infinate redirect loop
            // TODO put this in the app, not fixed in the client
            $blockTime = @$_SESSION['mwauth__block_passive_login'];
            $doPassiveLogin = (empty($blockTime) or $blockTime < time());
            if ($doPassiveLogin) {

                // This will prevent additional passive login attempts
                $_SESSION['mwauth__block_passive_login'] = time() + 60; // 300 = 5 min

                // redirect to perform the passive login
                $loginUrl = $this->getLoginUrl( array_merge($params, array(

                    // just to tell the server that we want to do a passive login here
                    // so if the user is not logged in, return them to returnTo
                    'passive' => true,
                )) );

                $this->redirect( $loginUrl );
            }
        }
    }

    /**
     * Will use the access token that was created when the user signed in and
     * update the users properties on the sso server
     * @param array $values New values (e.g. email)
     * @return League\OAuth2\Client\Provider\GenericResourceOwner
     */
    public function updateUser($values)
    {
        try {

            $provider = new Provider( array(
                'clientId'                => $this->options['client_id'],
                'clientSecret'            => $this->options['client_secret'],
                'redirectUri'             => null,
                'urlAuthorize'            => $this->options['server_url'] . '/oauth/authorize',
                'urlAccessToken'          => $this->options['server_url'] . '/oauth/token',
                'urlResourceOwnerDetails' => $this->options['server_url'] . '/api/account',
            ) );

            // get access token from the server. if not found, throw exception
            // this access_token is set when the user makes an auth request (login)
            $accessToken = $this->getAccessToken();

            // using access token, send update request to api
            // update session values if successfully updates
            $resourceOwner = $provider->updateResourceOwner($accessToken, $values);

            $this->storage['attributes'] = $resourceOwner->toArray();

            return $resourceOwner;

        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

            // Failed to get the access token
            exit($e->getMessage());

        }
    }

    /**
     * Delete an account (if the user wishes to do so)
     * @param array $values New values (e.g. email)
     * @return boolean
     */
    public function deleteUser()
    {
        try {

            $provider = new Provider( array(
                'clientId'                => $this->options['client_id'],
                'clientSecret'            => $this->options['client_secret'],
                'redirectUri'             => null,
                'urlAuthorize'            => $this->options['server_url'] . '/oauth/authorize',
                'urlAccessToken'          => $this->options['server_url'] . '/oauth/token',
                'urlResourceOwnerDetails' => $this->options['server_url'] . '/api/account',
            ) );

            // get access token from the server. if not found, throw exception
            // this access_token is set when the user makes an auth request (login)
            $accessToken = $this->getAccessToken();

            // using access token, send update request to api
            $provider->deleteResourceOwner($accessToken); // ??

            // Clear attributes as the user no longer exists
            $this->clearAttributes();

        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

            // Failed to get the access token
            exit($e->getMessage());

        }
    }

    /**
     * Will get the access token from storage. If it has expired it will fetch a
     * new one (refresh)
     * @return AccessToken
     */
    protected function getAccessToken()
    {
        // get access token from the server. if not found, throw exception
        // this access_token is set when the user makes an auth request (login)
        $accessToken = @$this->storage['access_token'];
        if (!$accessToken) {
            throw new \Exception('Access token not found.');
        }

        // access token may have expired, so we need to handle this too
        // if no refresh token is set, it will just return the expired token
        if ($accessToken->hasExpired() and $refreshToken = $accessToken->getRefreshToken()) {

            $provider = new Provider( array(
                'clientId'                => $this->options['client_id'],
                'clientSecret'            => $this->options['client_secret'],
                'redirectUri'             => null,
                'urlAuthorize'            => $this->options['server_url'] . '/oauth/authorize',
                'urlAccessToken'          => $this->options['server_url'] . '/oauth/token',
                'urlResourceOwnerDetails' => $this->options['server_url'] . '/api/account',
            ) );

            // get new access token
            $accessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $refreshToken,
            ]);

            // Purge old access token and store new access token to your data store.
            $this->storage['access_token'] = $accessToken;
        }

        return $accessToken;
    }

    /**
     * Will generate a url from a base url, and attach params (inc default returnTo)
     */
     protected function buildUrlWithParams($url, $params=array())
     {
         // set default returnTo to the existing page
         if (! array_key_exists('returnTo', $params))
             $params['returnTo'] = self::getCurrentUrl();

         if (! empty($params)) {
             $url.= '?' . http_build_query($params);
         }

         return $url;
     }

     /**
      * Perform a redirect to login, logout, etc
      */
     protected function redirect($url)
     {
         header('Location: ' . $url);
         die();
     }
}
