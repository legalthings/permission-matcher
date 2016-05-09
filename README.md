Legal Things - Permission matcher	
==================

With the permission matcher library, you can check whether a user is allowed to have access to specific resources.
Specifying resources and access control levels is up to the client of the library.

## Requirements

- [PHP](http://www.php.net) >= 5.6.0

_Required PHP extensions are marked by composer_

## Installation

The library can be installed using composer.

    composer require legalthings/permission-matcher

## How it works

The library exposes one function with which you can get a list of privileges for matching authz groups.
Authz groups can be anything you want, in the example below resource URIs are used, but you could also use a string of any format.
In the example we have a user that has certain permissions attached to him. We can then ask the `PermissionMatcher` class to extract the permissions of the users for a given authz group.
Note that you can use wildcards `*`.

```php
$matcher = new PermissionMatcher();

$permissionsThatSomeUserHas = [
    '/organizations/0001' => ['full-access'],
    '/organizations/0002?list=all' => 'list',
    '/organizations/0003/*/foo' => ['read', 'write']
];

echo $matcher->match($permissionsThatSomeUserHas, ['/organizations/0001']);
// outputs ['full-access']

echo $matcher->match($permissionsThatSomeUserHas, ['/organizations/0001', '/organizations/0003/random/foo']);
// outputs ['full-access', 'read', 'write']

echo $matcher->match($permissionsThatSomeUserHas, ['/organizations/0002']);
// outputs []

echo $matcher->match($permissionsThatSomeUserHas, ['/organizations/0002?list=all']);
// outputs ['list']

echo $matcher->match($permissionsThatSomeUserHas, ['/organizations/*']);
// outputs ['full-access', 'read', 'write', 'list']
```
