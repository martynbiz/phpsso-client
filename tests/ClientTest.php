<?php

use SSO\MWAuth\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Storage_mock
     */
    protected $storageMock;

    /**
     * @var array
     */
    protected $urls = array(
        'register_url' => 'http://sso.example.com/users',
        'login_url' => 'http://sso.example.com/session',
        'logout_url' => 'http://sso.example.com/session',
    );

    public function setUp()
    {
        // mock the auth adapter
        $this->storageMock = $this->getMockBuilder('SSO\\MWAuth\\Storage\\StorageInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function test_initialization()
    {
        $client = new Client($this->storageMock);

        $this->assertTrue($client instanceof Client);
    }

    // isAuthenticated

    public function test_is_athenticated_returns_true_when_storage_has_data()
    {
        // check save is never called
        $this->storageMock
            ->method('getContents')
            ->willReturn(array( 'name' => 'Martyn' ));

        $client = new Client($this->storageMock);

        $this->assertTrue($client->isAuthenticated());
    }

    public function test_is_athenticated_returns_false_when_storage_is_empty()
    {
        // check save is never called
        $this->storageMock
            ->method('getContents')
            ->willReturn(array());

        $client = new Client($this->storageMock);

        $this->assertFalse($client->isAuthenticated());
    }

    // getAttributes

    public function test_get_attributes_returns_contents_of_storage()
    {
        $contents = array( 'name' => 'Martyn' );

        // check save is never called
        $this->storageMock
            ->method('getContents')
            ->willReturn($contents);

        $client = new Client($this->storageMock);

        $this->assertEquals($contents, $client->getAttributes());
    }

    // clearAttributes

    public function test_clear_attributes_calls_storage_empty_contents_method()
    {
        // check save is never called
        $this->storageMock
            ->expects( $this->once() )
            ->method('emptyContents');

        $client = new Client($this->storageMock);

        $client->clearAttributes();
    }

    // getLoginUrl

    public function test_get_login_url_returns_url_with_return_to_parameter()
    {
        $client = new Client($this->storageMock, $this->urls);

        $expected = $this->urls['login_url'] . '?returnTo=http%3A%2F%2Fexample.com%2Fabout';
        $actual = $client->getLoginUrl(array(
            'returnTo' => 'http://example.com/about',
        ));

        $this->assertEquals($expected, $actual);
    }

    public function test_get_login_url_returns_url_with_default_return_to_parameter()
    {
        $client = new Client($this->storageMock, $this->urls);

        $expected = $this->urls['login_url'] . '?returnTo=http%3A%2F%2Flocalhost%2Fquestions';
        $actual = $client->getLoginUrl();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException SSO\MWAuth\Exception\MissingUrl
     */
    public function test_get_login_url_throws_exception_when_url_missing()
    {
        $client = new Client($this->storageMock); // no url given

        $actual = $client->getLoginUrl(array(
            'returnTo' => 'http://example.com',
        ));
    }

    // getLogoutUrl

    public function test_get_logout_url_returns_url_with_return_to_parameter()
    {
        $client = new Client($this->storageMock, $this->urls);

        $expected = $this->urls['logout_url'] . '?returnTo=http%3A%2F%2Fexample.com%2Fabout';
        $actual = $client->getLogoutUrl(array(
            'returnTo' => 'http://example.com/about',
        ));

        $this->assertEquals($expected, $actual);
    }

    public function test_get_logout_url_returns_url_with_default_return_to_parameter()
    {
        $client = new Client($this->storageMock, $this->urls);

        $expected = $this->urls['logout_url'] . '?returnTo=http%3A%2F%2Flocalhost%2Fquestions';
        $actual = $client->getLogoutUrl();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException SSO\MWAuth\Exception\MissingUrl
     */
    public function test_get_logout_url_throws_exception_when_url_missing()
    {
        $client = new Client($this->storageMock); // no url given

        $actual = $client->getLogoutUrl(array(
            'returnTo' => 'http://example.com',
        ));
    }

    // getRegisterUrl

    public function test_get_register_url_returns_url_with_return_to_parameter()
    {
        $client = new Client($this->storageMock, $this->urls);

        $expected = $this->urls['register_url'] . '?returnTo=http%3A%2F%2Fexample.com%2Fabout';
        $actual = $client->getRegisterUrl(array(
            'returnTo' => 'http://example.com/about',
        ));

        $this->assertEquals($expected, $actual);
    }

    public function test_get_register_url_returns_url_with_default_return_to_parameter()
    {
        $client = new Client($this->storageMock, $this->urls);

        $expected = $this->urls['register_url'] . '?returnTo=http%3A%2F%2Flocalhost%2Fquestions';
        $actual = $client->getRegisterUrl();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException SSO\MWAuth\Exception\MissingUrl
     */
    public function test_get_register_url_throws_exception_when_url_missing()
    {
        $client = new Client($this->storageMock); // no url given

        $actual = $client->getRegisterUrl(array(
            'returnTo' => 'http://example.com',
        ));
    }

    // getCurretUrl

    public function test_get_current_url()
    {
        $client = new Client($this->storageMock);

        $actual = $client->getCurrentUrl();

        $this->assertEquals('http://localhost/questions', $actual);
    }
}
