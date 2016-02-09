# MWAuth Client #

Framework agnostic class which provides access to session variables set from MWAuth authentication app.

## Installation ##

Install using composer

```
"minimum-stability": "dev",
"repositories": [
    {
        "url": "http://gitlab.metroworks.co.jp/sso/mwauth-client.git",
        "type": "git"
    },
],
"require" : {
    "sso/mwauth-client": "*@dev",
}
```

## Usage ##

Ensure that your app that you wish to authenticate from is using the same session as MWAuth (check save_path, redis, cookie domain etc)

To test, you can dump $_SESSION and see if the session variables set from MWAuth are present:

```
var_dump($_SESSION);
```

If they are there, then you're good to start setting up the client. Otherwise you'll need to debug session and cookie configurations in PHP or the current app. Enjoy :)

Assumming that the session variables are there, we'll configure the client for the MWAuth installation:

```
$storage = new \SSO\MWAuth\Storage\Session('jt_sso__');
$client = new \SSO\MWAuth\Client($storage, array(
    'register_url' => 'http://sso.jt.martyndev/users',
    'login_url' => 'http://sso.jt.martyndev/session',
    'logout_url' => 'http://sso.jt.martyndev/session',
));
```

Now we can gather information about the users session:

isAuthenticated

```
$result = $client->isAuthenticated();
```

getAttributes

```
$attributes = $client->getAttributes();
```

getLoginUrl

```
$attributes = $client->getLoginUrl(array(
    'returnTo' => 'http://www.example.com', // defaults to current page if not set
));
```

getRegisterUrl

```
$attributes = $client->getRegisterUrl(array(
    'returnTo' => 'http://www.example.com',  // defaults to current page if not set
));
```

getLogoutUrl

```
$attributes = $client->getLogoutUrl(array(
    'returnTo' => 'http://www.example.com',  // defaults to current page if not set
));
```

Logout URL will display a logout landing page, we don't logout on GET. However a POST with
_METHOD=DELETE parameter (browsers don't support DELETE method) will logout the user out and
redirect to the returnTo. The landing page is just to allow support for non-JS situations.

Something like the following can be used to make a POST/DELETE request to the logout URL:

```
<a href="#" class="logoutBtn">Logout</a>
<form id="logoutForm" method="POST" action="<?php echo $client->getLogoutUrl(); ?>">
    <input type="hidden" name="_METHOD" value="DELETE">
</form>
```

Note: The URLs returned by getLoginUrl, getRegisterUrl and getLogoutUrl can be a GET request (show form) or
a POST request for handling form submissions. Saves us having to define many URLs here.

getCurrentUrl

Useful helper method to build the URL from the $_SERVER if needed. Note: get*Url methods above will
set this automatically for returnTo if not given.

```
$attributes = $client->getCurrentUrl();
```

requireLogin

Will redirect the browser to the login screen, only if the user is not currently authenticated.
Otherwise, will do nothing.

```
$attributes = $client->forceLogin(array(
    'returnTo' => 'http://www.example.com',  // defaults to current page if not set
));
```

forceLogin

Will redirect the browser to the login screen even if the user is not currently
authenticated - the current session variables, if exist, will be deleted.

```
$attributes = $client->forceLogin(array(
    'returnTo' => 'http://www.example.com',  // defaults to current page if not set
));
```
