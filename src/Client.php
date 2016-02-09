<?php
namespace SSO\MWAuth;

use SSO\MWAuth\Storage\StorageInterface;
use SSO\MWAuth\Exception\MissingUrl as MissingUrlException;

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
    * Redirect the browser to the login form always, even if the user is already
    * authenticated
    * @param array $params e.g. GET params returnTo=http://...
    * @return void
    */
    public function forceLogin($params=array())
    {
        // we wanna destroy the current session variables otherwise the mwauth app
        // will return us to the app as we're already logged in.
        $this->clearAttributes();

        // no that we're signed out, we can requireLogin which will redirect to the
        // login page
        $this->requireLogin($params);
    }

    /**
    * Redirects to the login page if, and only if, the user is not authenticated
    * Otherwise, do nothing
    * @param array $params e.g. GET params returnTo=http://...
    * @return void
    */
    public function requireLogin($params=array())
    {
        // redirect to the login page
        // TODO do we have a header setter object for redirects? can't test header(...)
    }

    /**
    * Get the login URL, the same URL is used for GET and POST requests
    * @param array $params e.g. GET params returnTo=http://...
    * @return string
    */
    public function getLoginUrl($params=array())
    {
        if (! array_key_exists('login_url', $this->options))
            throw new MissingUrlException('Login URL not set in client');

        return $this->buildUrlWithParams($this->options['login_url'], $params);
    }

    /**
    * Get the logout URL, the same URL is used for GET and POST requests
    * @param array $params e.g. GET params returnTo=http://...
    * @return string
    */
    public function getLogoutUrl($params=array())
    {
        if (! array_key_exists('login_url', $this->options))
            throw new MissingUrlException('Login URL not set in client');

        return $this->buildUrlWithParams($this->options['login_url'], $params);
    }

    /**
    * Get the register URL, the same URL is used for GET and POST requests
    * @param array $params e.g. GET params returnTo=http://...
    * @return string
    */
    public function getRegisterUrl($params=array())
    {
        // set default returnTo to the existing page
        if (! array_key_exists('register_url', $this->options))
            throw new MissingUrlException('Register URL not set in client');

        return $this->buildUrlWithParams($this->options['register_url'], $params);
    }

    /**
     * Will generate a url from a base url, and attach params (inc default returnTo)
     */
     protected function buildUrlWithParams($url, $params=array())
     {
         // set default returnTo to the existing page
         if (! array_key_exists('returnTo', $params))
             $params['returnTo'] = self::getUrl();

         if (! empty($params)) {
             $url.= '?' . http_build_query($params);
         }

         return $url;
     }

    /**
     * Fetches the current URL, allows us to set default returnTo without having to
     * request this every call
     * @return string
     */
    public static function getUrl() { //($includePortNumber=false) {

        // get the protocol and domain e.g. http://mydomain.com
        $url  = @( isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] == 'on' ) ? 'https://'.$_SERVER["SERVER_NAME"] :  'http://'.$_SERVER["SERVER_NAME"];

        // include the port number e.g. mydomain.com:80
        // if($includePortNumber)
            $url .= ( (int) $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";

        // get the path to the resource e.g. /path/to/resource?params=should-be-included-too
        $url .= $_SERVER["REQUEST_URI"];

        return $url;
    }
}
