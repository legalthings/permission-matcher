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
    public function testMatchAliasString()
    {
        $permissions = [
            "admin" => "write",
            "guest" => "read"
        ];

        $this->assertArrayMatches(["write"], $this->matcher->match($permissions, ["admin"]));
        $this->assertArrayMatches(["write"], $this->matcher->match($permissions, ["admin", "foo"]));
        $this->assertArrayMatches(["read"], $this->matcher->match($permissions, ["guest"]));
        $this->assertArrayMatches([], $this->matcher->match($permissions, ["foo"]));
        $this->assertArrayMatches(["read", "write"], $this->matcher->match($permissions, ["admin", "guest"]));
    }

    public function testMatchAliasArray()
    {
        $permissions = [
            "admin" => ["read", "write"],
            "guest" => ["read"],
            "owner" => ["manage"]
        ];

        $this->assertArrayMatches(["read", "write"], $this->matcher->match($permissions, ["admin"]));
        $this->assertArrayMatches(["read", "write"], $this->matcher->match($permissions, ["admin", "foo"]));
        $this->assertArrayMatches(["read"], $this->matcher->match($permissions, ["guest"]));
        $this->assertArrayMatches([], $this->matcher->match($permissions, ["foo"]));
        $this->assertArrayMatches(["read", "write", "manage"], $this->matcher->match($permissions, ["admin", "guest", "owner"]));
    }

    public function testMatchAliasNested()
    {
        $permissions = [
            "admin" => ["read", "write"],
            "admin.support" => ["support"],
            "admin.dev" => ["dev"],
            "manager" => ["manage"],
            "manager.business" => ["manage", "business"]
        ];

        $this->assertArrayMatches(["read", "write"], $this->matcher->match($permissions, ["admin"]));
        $this->assertArrayMatches(["read", "write", "support"], $this->matcher->match($permissions, ["admin.support"]));
        $this->assertArrayMatches(["read", "write", "dev"], $this->matcher->match($permissions, ["admin.dev"]));
        $this->assertArrayMatches(["manage"], $this->matcher->match($permissions, ["manager"]));
        $this->assertArrayMatches(["manage", "business"], $this->matcher->match($permissions, ["manager.business"]));
        $this->assertArrayMatches([], $this->matcher->match($permissions, ["foo"]));
    }

    public function testMatchAliasWildcard()
    {
        $permissions = [
            "admin" => ["read", "write"],
            "admin.support" => ["support"],
            "admin.dev" => ["dev"],
            "manager" => ["manage"],
            "manager.business" => ["manage", "business"]
        ];

        $this->assertArrayMatches(["read", "write"], $this->matcher->match($permissions, ["admin"]));
        $this->assertArrayMatches(["read", "write", "support", "dev"], $this->matcher->match($permissions, ["admin.*"]));
        $this->assertArrayMatches(["manage"], $this->matcher->match($permissions, ["manager"]));
        $this->assertArrayMatches(["manage", "business"], $this->matcher->match($permissions, ["manager.*"]));
        $this->assertArrayMatches([], $this->matcher->match($permissions, ["foo"]));
        $this->assertArrayMatches(["read", "write", "support", "dev", "manage", "business"], $this->matcher->match($permissions, ["manager.*", "admin.*"]));
    }

    public function testMatchResourceNested()
    {
        $permissions = [
            "/admin" => ["read", "write"],
            "/admin/support" => ["support"],
            "/admin/dev" => ["dev"],
            "/manager" => ["manage"],
            "/manager/business" => ["manage", "business"]
        ];

        $this->assertArrayMatches(["read", "write"], $this->matcher->match($permissions, ["/admin"]));
        $this->assertArrayMatches(["read", "write", "support"], $this->matcher->match($permissions, ["/admin/support"]));
        $this->assertArrayMatches(["read", "write", "dev"], $this->matcher->match($permissions, ["/admin/dev"]));
        $this->assertArrayMatches(["manage"], $this->matcher->match($permissions, ["/manager"]));
        $this->assertArrayMatches(["manage", "business"], $this->matcher->match($permissions, ["/manager/business"]));
        $this->assertArrayMatches([], $this->matcher->match($permissions, ["/foo"]));
    }

    public function testMatchResourceWithQueryParams()
    {
        $permissions = [
            "/admin" => ["read", "write"],
            "/admin?role=leader" => ["leader"],
            "/admin?role=support" => ["support"],
            "/admin/super?job=lawyer&from=amsterdam" => ["super", "lawyer", "amsterdam"]
        ];

        $this->assertArrayMatches(["read", "write"], $this->matcher->match($permissions, ["/admin"]));
        $this->assertArrayMatches(["read", "write", "leader"], $this->matcher->match($permissions, ["/admin?role=leader"]));
        $this->assertArrayMatches(["read", "write", "support"], $this->matcher->match($permissions, ["/admin?role=support"]));
        $this->assertArrayMatches(["read", "write", "amsterdam", "lawyer", "super"], $this->matcher->match($permissions, ["/admin/super?job=lawyer&from=amsterdam"]));
        $this->assertArrayMatches(["read", "write", "amsterdam", "lawyer", "super"], $this->matcher->match($permissions, ["/admin/super?from=amsterdam&job=lawyer"]));
        $this->assertArrayMatches(["read", "write"], $this->matcher->match($permissions, ["/admin/super?job=lawyer"]));
        $this->assertArrayMatches(["read", "write"], $this->matcher->match($permissions, ["/admin/super"]));
        $this->assertArrayMatches([], $this->matcher->match($permissions, ["/foo"]));
    }

    public function testInvalidMatchArguments()
    {
        $this->markTestIncomplete("Passing invalid arguments not tested");
    }
}
