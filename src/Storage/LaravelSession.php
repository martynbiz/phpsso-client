<?php
namespace MartynBiz\Sso\Storage;

/**
 * This ArrayAccess implementation is designed to allow session variables to be
 * accessed in an OOP method (ie. for mocking during unit testing ) BUT not put
 * objects into session (e.g. like Zend\Session does)
 */
class LaravelSession implements StorageInterface
{
    // protected $contents;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var
     */
    protected $session;

    public function __construct($session, $namespace, $values=array())
    {
        // if namespace doesn't exist, create it as an empty array
        if (! $data = $session->get($namespace))
            $session->put($namespace, []);

        // // merge values into the current namespace (don't think we wanna overwrite)
        // $_SESSION[$namespace] = array_merge($_SESSION[$namespace], $values);

        // store the name space as we'll use this to fetch the data from session
        $this->namespace = $namespace;

        $this->session = $session;

        // // create referrence to namespace
        // $this->contents = &$_SESSION[$namespace];
    }

    public function offsetExists($index) {

        $data = $this->session->get($this->namespace);

        return isset($data[$index]);
    }

    public function offsetGet($index) {

        $data = $this->session->get($this->namespace);

        if($this->offsetExists($index)) {
            return $data[$index];
        }
        return false;
    }

    public function offsetSet($index, $value) {
        if($index) {
            $data = $this->session->get($this->namespace);
            $data[$index] = $value;
        } else {
            // what does this do???
            $data = $this->session->get($this->namespace);
            $data[] = $value;
        }
        $this->session->set($this->namespace, $data);
        return true;

    }

    public function offsetUnset($index) {
        $data = $this->session->get($this->namespace);
        unset($data[$index]);
        $this->session->set($this->namespace, $data);
        return true;
    }

    /**
     * Will get the contents from wherever stored (session, db) and return as a
     * PHP associative array
     * @return array
     */
    public function getContents() {
        return $this->session->get($this->namespace);
    }

    /**
     * Will empty the contents of the storage (e.g. clear session sso variables )
     * @return void
     */
    public function emptyContents() {
        $session->put($this->namespace, []);
    }
}
