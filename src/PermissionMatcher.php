<?php

namespace LegalThings;

/**
 * TODO: write this
 */
class PermissionMatcher
{
    /**
     * Get a flat list of privileges for matching authz groups
     *
     * @param array|object $permissions
     * @param array        $authzGroups
     * @return array
     */
    public function match($permissions, array $authzGroups)
    {
        $privileges = [];

        foreach ($permissions as $permissionAuthzGroup => $permissionPrivileges) {
            $matchingAuthzGroup = $this->getMatchingAuthzGroup($permissionAuthzGroup, $authzGroups);
            
            if (!$matchingAuthzGroup) {
                continue;
            }
            
            $privileges[] = $permissionPrivileges;
        }

        return $this->flatten($privileges);
    }
    
    /**
     * Get a list of privileges for matching authz groups containing more information
     * Returns an array of objects where the privilege is the key and authzgroups the value
     *
     * @param array|object $permissions
     * @param array        $authzGroups
     * @return array
     */
    public function matchFull($permissions, array $authzGroups)
    {
        $privileges = [];

        foreach ($permissions as $permissionAuthzGroup => $permissionPrivileges) {
            $matchingAuthzGroup = $this->getMatchingAuthzGroup($permissionAuthzGroup, $authzGroups);
            
            if (!$matchingAuthzGroup) {
                continue;
            }
            
            $privileges = $this->addAuthzGroupsToPrivileges($privileges, $permissionPrivileges, [
                $permissionAuthzGroup, $matchingAuthzGroup
            ]);
        }

        return $privileges;
    }

    /**
     * Check if one of the authz groups match
     *
     * @param string $permissionAuthzGroup
     * @param array  $authzGroups
     * @return string|null
     */
    protected function getMatchingAuthzGroup($permissionAuthzGroup, array $authzGroups)
    {
        $invert = $this->stringStartsWith($permissionAuthzGroup, '!');
        
        if ($invert) {
            $permissionAuthzGroup = substr($permissionAuthzGroup, 1);
        }
        
        $matches = [];
        
        foreach ($authzGroups as $authzGroup) {
            $match = $this->authzGroupsAreEqual($permissionAuthzGroup, $authzGroup);
            
            if ($match && $invert) {
                return null;
            }
            
            if ($match && !$invert || !$match && $invert) {
                $matches[] = $authzGroup;
            }
        }
        
        return !empty($matches) ? $matches[0] : null;
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
        $regex = '^' . str_replace('[^/]+', '\\*', preg_quote($pattern, '~')) . '$';
        $regex = str_replace('\\*', '(.*)', $regex);

        $match = preg_match('~' . $regex . '~i', $subject);

        return $match;
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
    
    /**
     * Populate an array of privileges with their corresponding authz groups
     *
     * @param  array          $privileges            The resulting array
     * @param  string|array   $authzGroupsPrivileges The privileges that the authzgroup has
     * @param  array          $authzGroups
     * @return array          $privileges
     */
    protected function addAuthzGroupsToPrivileges(array $privileges, $authzGroupsPrivileges, array $authzGroups)
    {
        $authzGroupsPrivileges = !is_string($authzGroupsPrivileges) ? $authzGroupsPrivileges : [$authzGroupsPrivileges];
        
        foreach($authzGroupsPrivileges as $privilege) {
            $current = !empty($privileges[$privilege]) ? $privileges[$privilege] : [];
            $privileges[$privilege] = array_unique(array_merge($current, $authzGroups));
        }
        
        return $privileges;
    }
    
    /**
     * Check if a string starts with given substring
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    protected function stringStartsWith($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0 ? true : false;
    }
}
