<?php

namespace LegalThings;

/**
 * TODO: write this
 */
class PermissionMatcher
{
    /**
     * Array of delimiters used to seperated nested authz groups
     * These delimiters should not be combined in a single authz group or permission
     * @var array
     */
    public static $delimiters = [".", "/"];

    /**
     * Get a list of privileges for matching authz groups
     *
     * @param array|object $permissions
     * @param array        $authzGroups
     * @return array
     */
    public function match($permissions, array $authzGroups)
    {
        $privileges = [];

        foreach ($permissions as $permissionAuthzGroup => $permissionPrivileges) {
            if ($this->hasMatchingAuthzGroup($permissionAuthzGroup, $authzGroups)) {
                $privileges[] = $permissionPrivileges;
            }
        }

        return $this->flatten($privileges);
    }

    /**
     * Check if authz groups match
     *
     * @param string $permissionAuthzGroup
     * @param string $authzGroup
     * @return boolean
     */
    protected function authzGroupsAreEqual($permissionAuthzGroup, $authzGroup)
    {
        // remove first character if its a delimiter
        $permissionAuthzGroup = in_array($permissionAuthzGroup[0], static::$delimiters) ? substr($permissionAuthzGroup, 1) : $permissionAuthzGroup;

        // compare query parameters
        $queryParamsMatch = $this->compareStringsByQueryParameters($authzGroup, $permissionAuthzGroup);

        // remove query parameters
        $permissionAuthzGroup = strtok($permissionAuthzGroup, '?');
        $authzGroup = strtok($authzGroup, '?');

        // delimit authz group
        $delimiter = $this->getStringDelimiter($authzGroup, static::$delimiters);
        $groups = isset($delimiter) ? explode($delimiter, $authzGroup) : [$authzGroup];
        $concattedAuthzGroup = "";

        // match nested authz group and check wildcards
        foreach ($groups as $key => $group) {
            $concattedAuthzGroup .= empty($concattedAuthzGroup) ? $group : "${delimiter}${group}";

            if ($concattedAuthzGroup === $permissionAuthzGroup && $queryParamsMatch) return true;

            if (strpos($concattedAuthzGroup, "*") > -1) {
                $match = preg_match("`^${concattedAuthzGroup}`", $permissionAuthzGroup);
                if ($match && $queryParamsMatch) return true;
            }
        }

        return false;
    }

    /**
     * Check if one of the authz groups match
     *
     * @param string $permissionAuthzGroup
     * @param array  $authzGroups
     */
    protected function hasMatchingAuthzGroup($permissionAuthzGroup, array $authzGroups)
    {
        foreach ($authzGroups as $authzGroup) {
            if ($this->authzGroupsAreEqual($permissionAuthzGroup, $authzGroup)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Turn a 2 dimensional privilege array into a list of privileges
     *
     * @param array $input
     * @return array
     */
    protected function flatten($input)
    {
        $list = [];

        foreach ($input as $item) {
            $list = array_merge($list, (array)$item);
        }

        return array_unique($list);
    }

    /**
     * Get the delimiter used in a string
     *
     * @param string $string
     * @param array  $delimiters
     * @return string|null
     */
    protected function getStringDelimiter($string, array $delimiters)
    {
        foreach ($delimiters as $delimiter) {
            if (strpos($string, $delimiter) > -1) {
                return $delimiter;
            }
        }

        return null;
    }

    /**
     * Get the query parameters used in a string
     *
     * @param string $string
     * @return array
     */
    protected function getStringQueryParameters($string)
    {
        $parts = parse_url($string);
        $params = [];
        if (isset($parts['query'])) parse_str($parts['query'], $params);
        return $params;
    }

    /**
     * Compare the query parameters of two strings
     *
     * @param string $authzGroup
     * @param string $permissionAuthzGroup
     * @return boolean
     */
    protected function compareStringsByQueryParameters($authzGroup, $permissionAuthzGroup)
    {
        $authzGroupQueryParams = $this->getStringQueryParameters($authzGroup);
        $permissionAuthzGroupQueryParams = $this->getStringQueryParameters($permissionAuthzGroup);
        sort($authzGroupQueryParams);
        sort($permissionAuthzGroupQueryParams);

        if (empty($permissionAuthzGroupQueryParams)) return true;

        return $permissionAuthzGroupQueryParams === $authzGroupQueryParams;
    }
}
