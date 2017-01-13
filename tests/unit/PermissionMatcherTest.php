<?php

namespace LegalThings;

/**
 * @covers \LegalThings\PermissionMatcher
 */
class PermissionMatcherTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var PermissionMatcher
     */
    protected $matcher;

    protected function _before()
    {
        $this->matcher = new PermissionMatcher();
    }

    protected function assertArrayMatches($expected, $source)
    {
        sort($expected);
        sort($source);
        $this->assertEquals($expected, $source);
    }


    // tests
    public function testMatchString()
    {
        $permissions = [
            'admin' => 'write',
            'guest' => 'read'
        ];

        $this->assertArrayMatches(['write'], $this->matcher->match($permissions, ['admin']));
        $this->assertArrayMatches(['write'], $this->matcher->match($permissions, ['admin', 'foo']));
        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['guest']));
        $this->assertArrayMatches([], $this->matcher->match($permissions, ['foo']));
        $this->assertArrayMatches(['read', 'write'], $this->matcher->match($permissions, ['admin', 'guest']));
    }

    public function testMatchArray()
    {
        $permissions = [
            'admin' => ['read', 'write'],
            'guest' => ['read'],
            'owner' => ['manage']
        ];

        $this->assertArrayMatches(['read', 'write'], $this->matcher->match($permissions, ['admin']));
        $this->assertArrayMatches(['read', 'write'], $this->matcher->match($permissions, ['admin', 'foo']));
        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['guest']));
        $this->assertArrayMatches([], $this->matcher->match($permissions, ['foo']));
        $this->assertArrayMatches(['read', 'write', 'manage'], $this->matcher->match($permissions, ['admin', 'guest', 'owner']));
    }

    public function testMatchNested()
    {
        $permissions = [
            'admin' => 'read',
            'admin.support' => 'write',
            'admin.dev' => 'develop'
        ];

        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['admin']));
        $this->assertArrayMatches(['write'], $this->matcher->match($permissions, ['admin.support']));
        $this->assertArrayMatches(['read', 'write'], $this->matcher->match($permissions, ['admin', 'admin.support']));
    }

    public function testMatchWildcard()
    {
        $permissions = [
            'admin' => 'read',
            'admin.support' => 'write',
            'admin.dev' => 'develop',
            'admin.dev.tester' => 'test',
            'guest' => 'find',
            'guest.support' => 'sing',
            '*.support' => 'dance'
        ];

        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['admin']));
        $this->assertArrayMatches(['write', 'develop', 'test'], $this->matcher->match($permissions, ['admin.*']));
        $this->assertArrayMatches(['test'], $this->matcher->match($permissions, ['admin.*.*']));
        $this->assertArrayMatches(['read', 'write', 'develop', 'test'], $this->matcher->match($permissions, ['admin', 'admin.*']));
        $this->assertArrayMatches(['develop', 'test'], $this->matcher->match($permissions, ['admin.d*']));

        $this->assertArrayMatches(['write', 'dance'], $this->matcher->match($permissions, ['admin.support']));
        $this->assertArrayMatches(['sing', 'dance'], $this->matcher->match($permissions, ['guest.support']));
        $this->assertArrayMatches(['dance'], $this->matcher->match($permissions, ['foo.support']));
        $this->assertArrayMatches(['write', 'sing', 'dance'], $this->matcher->match($permissions, ['*.support']));
    }

    public function testMatchCaseInsensitivePath()
    {
        $permissions = [
            'AdMiN' => 'read'
        ];

        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['admin']));
        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['ADMIN']));
        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['AdMiN']));
        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['aDmIn']));
    }

    public function testMatchResource()
    {
        $permissions = [
            '/admin' => 'read',
            '/admin/support' => 'write',
            '/admin/dev' => 'develop',
            '/bar/' => 'drink'
        ];

        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['/admin']));
        $this->assertArrayMatches(['write'], $this->matcher->match($permissions, ['/admin/support']));
        $this->assertArrayMatches(['read', 'write'], $this->matcher->match($permissions, ['/admin', '/admin/support']));

        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['/admin/']));
        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['/admin/?']));
        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['/admin?']));

        $this->assertArrayMatches(['drink'], $this->matcher->match($permissions, ['/bar']));
    }

    public function testMatchResourceWithQueryParams()
    {
        $permissions = [
            '/admin' => 'read',
            '/admin?role=support' => 'write',
            '/admin?role=dev' => 'develop',
            '/admin?job=lawyer&from=amsterdam' => 'party'
        ];

        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['/admin']));
        $this->assertArrayMatches(['write'], $this->matcher->match($permissions, ['/admin?role=support']));
        $this->assertArrayMatches(['read', 'write'], $this->matcher->match($permissions, ['/admin', '/admin?role=support']));

        $this->assertArrayMatches(['party'], $this->matcher->match($permissions, ['/admin?job=lawyer&from=amsterdam']));
        $this->assertArrayMatches(['party'], $this->matcher->match($permissions, ['/admin?from=amsterdam&job=lawyer']));

        $this->assertArrayMatches([], $this->matcher->match($permissions, ['/admin/super?job=lawyer']));
        $this->assertArrayMatches([], $this->matcher->match($permissions, ['/foo']));
    }

    public function testMatchWildcardResource()
    {
        $permissions = [
            '/admin' => 'read',
            '/admin/support' => 'write',
            '/admin/dev' => 'develop',
            '/admin/dev/tester' => 'test',
            '/guest' => 'find',
            '/guest/support' => 'sing',
            '/*/support' => 'dance'
        ];

        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['/admin']));
        $this->assertArrayMatches(['write', 'develop', 'test'], $this->matcher->match($permissions, ['/admin/*']));
        $this->assertArrayMatches(['test'], $this->matcher->match($permissions, ['/admin/*/*']));
        $this->assertArrayMatches(['read', 'write', 'develop', 'test'], $this->matcher->match($permissions, ['/admin', '/admin/*']));
        $this->assertArrayMatches(['develop', 'test'], $this->matcher->match($permissions, ['/admin/d*']));

        $this->assertArrayMatches(['write', 'dance'], $this->matcher->match($permissions, ['/admin/support']));
        $this->assertArrayMatches(['sing', 'dance'], $this->matcher->match($permissions, ['/guest/support']));
        $this->assertArrayMatches(['dance'], $this->matcher->match($permissions, ['/foo/support']));
        $this->assertArrayMatches(['write', 'sing', 'dance'], $this->matcher->match($permissions, ['/*/support']));
    }

    public function testMatchCaseInsensitiveQueryParams()
    {
        $permissions = [
            '/admin?ROlE=support' => 'write',
            '/admin?Job=lawyer&FROM=amsterdam' => 'party'
        ];

        $this->assertArrayMatches(['write'], $this->matcher->match($permissions, ['/admin?role=support']));
        $this->assertArrayMatches(['write'], $this->matcher->match($permissions, ['/admin?RolE=support']));
        $this->assertArrayMatches(['party'], $this->matcher->match($permissions, ['/admin?joB=lawyer&FroM=amsterdam']));
    }
    
    public function testMatchInverted()
    {
        $permissions = [
            '!admin' => 'read',
            'admin' => ['read', 'write']
        ];

        $this->assertArrayMatches(['read', 'write'], $this->matcher->match($permissions, ['admin']));
        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['guest']));
        $this->assertArrayMatches(['read'], $this->matcher->match($permissions, ['foo']));
    }
    
    public function testMatchReverse()
    {
        $permissions = [
            'admin' => 'read',
            'admin.support' => 'write',
            'admin.dev' => 'develop',
            'admin.dev.tester' => 'test',
            'guest' => 'find',
            'guest.support' => 'sing',
            '*.support' => 'dance'
        ];

        $this->assertEquals([
            'read' => ['admin']
        ], $this->matcher->match($permissions, ['admin'], true));
        
        $this->assertEquals([
            'write' => ['admin.support', 'admin.*'],
            'develop' => ['admin.dev', 'admin.*'],
            'test' => ['admin.dev.tester', 'admin.*']
        ], $this->matcher->match($permissions, ['admin.*'], true));
        
        $this->assertEquals([
            'test' => ['admin.dev.tester', 'admin.*.*']
        ], $this->matcher->match($permissions, ['admin.*.*'], true));
        
        $this->assertEquals([
            'read' => ['admin'],
            'write' => ['admin.support', 'admin.*'],
            'develop' => ['admin.dev', 'admin.*'],
            'test' => ['admin.dev.tester', 'admin.*']
        ], $this->matcher->match($permissions, ['admin', 'admin.*'], true));
        
        $this->assertEquals([
            'develop' => ['admin.dev', 'admin.d*'],
            'test' => ['admin.dev.tester', 'admin.d*']
        ], $this->matcher->match($permissions, ['admin.d*'], true));
    }
}
