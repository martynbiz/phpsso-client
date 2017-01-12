# MWAuth Client #

Framework agnostic library which provides access to session variables set from MWAuth authentication app.

TODO

* tests for session class (in cli, can define global $_SESSION perhaps?)

## Installation ##

Install using composer

```
composer require martynbiz/phpsso-client
```

## Usage ##

```
$storage = new \SSO\MWAuth\Storage\Session('jt_sso__');
$client = new \SSO\MWAuth\Client($storage, array(
    'client_id' => 'japantravel',
    'client_secret' => 'qwertyuiop1234567890',
    'server_url' => 'http://phpsso.martyndev',
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
