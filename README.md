# MWAuth Client #

Framework agnostic class which provides access to session variables set from MWAuth authentication app.

## Installation ##

Install using composer

```
"minimum-stability": "dev",
"repositories": [
    {
        "url": "http://gitlab.metroworks.co.jp/atsumarecq/acq.git",
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

If they are there, then you're good to start setting up the client. Otherwise you'll need to debug session and cookie configurations in PHP or the current app.

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
$result = $this->isAuthenticated();
```

getAttributes

```
$attributes = $this->getAttributes();
```

getLoginUrl

```
$attributes = $this->getLoginUrl(array(
    'returnTo' => 'http://www.example.com', // defaults to current page if not set
));
```

getRegisterUrl

```
$attributes = $this->getRegisterUrl(array(
    'returnTo' => 'http://www.example.com',  // defaults to current page if not set
));
```

getLogoutUrl

```
$attributes = $this->getLogoutUrl(array(
    'returnTo' => 'http://www.example.com',  // defaults to current page if not set
));
```

Note: The URLs returned by getLoginUrl, getRegisterUrl and getLogoutUrl can be a GET request (show form) or
a POST request for handling form submissions. Saves us having to define many URLs here.

forceLogin

Will destroy the current session variables and redirect the user to the login screen of MWAuth app.

```
$attributes = $this->forceLogin(array(
    'returnTo' => 'http://www.example.com',  // defaults to current page if not set
));
```

requireLogin

Will only redirect the browser to the login screen if the user is not currently authenticated.
Otherwise, will do nothing.

```
$attributes = $this->forceLogin(array(
    'returnTo' => 'http://www.example.com',  // defaults to current page if not set
));
```
