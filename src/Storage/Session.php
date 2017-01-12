<?php
namespace MartynBiz\Sso\Storage;

/**
 * This ArrayAccess implementation is designed to allow session variables to be
 * accessed in an OOP method (ie. for mocking during unit testing ) BUT not put
 * objects into session (e.g. like Zend\Session does)
 */
class Session implements StorageInterface
{
    // protected $contents;

    /**
     * @var string
     */
    protected $namespace;

    public function __construct($namespace, $values=array())
    {
        // if namespace doesn't exist, create it as an empty array
        if (! array_key_exists($namespace, $_SESSION))
            $_SESSION[$namespace] = array();

        // merge values into the current namespace (don't think we wanna overwrite)
        $_SESSION[$namespace] = array_merge($_SESSION[$namespace], $values);

        // store the name space as we'll use this to fetch the data from session
        $this->namespace = $namespace;

        // // create referrence to namespace
        // $this->contents = &$_SESSION[$namespace];
    }

    public function offsetExists($index) {
        return isset($_SESSION[$this->namespace][$index]);
    }

    public function offsetGet($index) {
        if($this->offsetExists($index)) {
            return $_SESSION[$this->namespace][$index];
        }
        return false;
    }

    public function offsetSet($index, $value) {
        if($index) {
            $_SESSION[$this->namespace][$index] = $value;
        } else {
            $_SESSION[$this->namespace][] = $value;
        }
        return true;

    }

    public function offsetUnset($index) {
        unset($_SESSION[$this->namespace][$index]);
        return true;
    }

    /**
     * Will get the contents from wherever stored (session, db) and return as a
     * PHP associative array
     * @return array
     */
    public function getContents() {
        return $_SESSION[$this->namespace];
    }

    /**
     * Will empty the contents of the storage (e.g. clear session sso variables )
     * @return void
     */
    public function emptyContents() {
        $_SESSION[$this->namespace] = array();
    }
}
