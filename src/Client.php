<?php
namespace SSO\MWAuth;

use SSO\MWAuth\Storage\StorageInterface;
use SSO\MWAuth\Exception\MissingUrl as MissingUrlException;
use League\OAuth2\Client\Provider\GenericProvider;

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
        // set default options
        $this->options = array_merge( array(
            // 'namespace' => 'mwauth__', // this is the namespace in the session we'll tap into
        ), $options);

        // set our storage object
        $this->storage = $storage; //new Session($options['namespace']);
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
        return $this->storage->getContents();
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
        // TODO how to set returnTo, this just assumes that it comes from GET returnTo after
        // redirect from

        // setup provider from options (e.g. client_id)
        $provider = new GenericProvider( array(
            'clientId'                => $this->options['client_id'],
            'clientSecret'            => $this->options['client_secret'],
            'redirectUri'             => $params['returnTo'], //'http://en.jt.martyndev/login',
            'urlAuthorize'            => $this->options['server_url'] . '/oauth/authorize',
            'urlAccessToken'          => $this->options['server_url'] . '/oauth/access_token',
            'urlResourceOwnerDetails' => $this->options['server_url'] . '/api/getaccount',
        ) );

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

                // Using the access token, we may look up details about the
                // resource owner.
                $resourceOwner = $provider->getResourceOwner($accessToken);
                $attributes = $resourceOwner->toArray();

                // TODO write session directly?
                $_SESSION[ $this->options['session_namespace'] ] = $resourceOwner->toArray();

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
    public function passiveLogin($params=array())
    {
        // only trigger remember me code if not authenticated, we don't need this
        // at all if they are already signed in (duh)
        if (! $this->isAuthenticated()) {

            // check if we have checked the login already,
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

    // /**
    //  * Will empty the session variables for an authenticated session
    //  */
    // public function clearSession()
    // {
    //     $_SESSION[ $this->options['session_namespace'] ] = array();
    // }

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
