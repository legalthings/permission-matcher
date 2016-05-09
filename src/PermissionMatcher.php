<?php

namespace LegalThings;

/**
 * TODO: write this
 */
class PermissionMatcher
{
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

        // exit();
        return $this->flatten($privileges);
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
     * Check if authz groups match
     *
     * @param string $permissionAuthzGroup
     * @param string $authzGroup
     * @return boolean
     */
    protected function authzGroupsAreEqual($permissionAuthzGroup, $authzGroup)
    {
        return $this->pathsAreEqual($permissionAuthzGroup, $authzGroup) && 
            $this->queryParamsAreEqual($permissionAuthzGroup, $authzGroup);
    }

    /**
     * Compare the paths of two authz groups
     *
     * @param string $permissionAuthzGroup
     * @param string $authzGroup
     * @return boolean
     */
    protected function pathsAreEqual($permissionAuthzGroup, $authzGroup)
    {
        $permissionAuthzGroupPath = rtrim(strtok($permissionAuthzGroup, '?'), '/');
        $authzGroupPath = rtrim(strtok($authzGroup, '?'), '/');

        return $this->matchAuthzGroupPaths($permissionAuthzGroupPath, $authzGroupPath) ||
            $this->matchAuthzGroupPaths($authzGroupPath, $permissionAuthzGroupPath);
    }

    /**
     * Check if one paths mathes the other 
     *
     * @param string $pattern
     * @param string $subject
     * @return boolean
     */
    protected function matchAuthzGroupPaths($pattern, $subject)
    {
        $regex = '^' . str_replace('[^/]+', '\\*', preg_quote($pattern, '~')) . '$'; // @todo: str_replace does not replace anything here?
        $regex = str_replace('\\*', '(.*)', $regex);

        return preg_match('~' . $regex . '~i', $subject);
    }


    /**
     * Compare the query parameters of two authz groups
     *
     * @param string $permissionAuthzGroup
     * @param string $authzGroup
     * @return boolean
     */
    protected function queryParamsAreEqual($permissionAuthzGroup, $authzGroup)
    {
        $authzGroupQueryParams = array_change_key_case($this->getStringQueryParameters($authzGroup), CASE_LOWER);
        $permissionAuthzGroupQueryParams = array_change_key_case($this->getStringQueryParameters($permissionAuthzGroup), CASE_LOWER);

        ksort($authzGroupQueryParams);
        ksort($permissionAuthzGroupQueryParams);

        return $permissionAuthzGroupQueryParams === $authzGroupQueryParams;
    }

    /**
     * Get the query parameters used in a string
     *
     * @param string $string
     * @return array
     */
    protected function getStringQueryParameters($string)
    {
        $query = parse_url($string, PHP_URL_QUERY);

        $params = [];
        if ($query) parse_str($query, $params);

        return $params;
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
}
