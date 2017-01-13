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
     * @param boolean      $reverse     Returns an array where the priviliges are the keys and authzgroups the values
     * @return array
     */
    public function match($permissions, array $authzGroups, $reverse = false)
    {
        $privileges = [];

        foreach ($permissions as $permissionAuthzGroup => $permissionPrivileges) {
            $matchingAuthzGroup = $this->hasMatchingAuthzGroup($permissionAuthzGroup, $authzGroups);
            
            if (!$matchingAuthzGroup) {
                continue;
            }
            
            if ($reverse) {
                $privileges = $this->addAuthzGroupsToPrivileges($privileges, $permissionPrivileges, [$permissionAuthzGroup, $matchingAuthzGroup]);
            } else {
                $privileges[] = $permissionPrivileges;
            }
        }

        return $reverse ? $privileges : $this->flatten($privileges);
    }

    /**
     * Check if one of the authz groups match
     *
     * @param string $permissionAuthzGroup
     * @param array  $authzGroups
     * @return string|boolean
     */
    protected function hasMatchingAuthzGroup($permissionAuthzGroup, array $authzGroups)
    {
        foreach ($authzGroups as $authzGroup) {
            if ($this->authzGroupsAreEqual($permissionAuthzGroup, $authzGroup)) {
                return $authzGroup;
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
        $regex = '^' . str_replace('[^/]+', '\\*', preg_quote($pattern, '~')) . '$';
        $regex = str_replace('\\*', '(.*)', $regex);

        $invert = $this->stringStartsWith($pattern, '!');
        $match = preg_match('~' . $regex . '~i', $subject);

        return $invert ? !$match : $match;
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
     * @return array          $priviliges
     */
    protected function addAuthzGroupsToPrivileges(array $privileges, $authzGroupsPrivileges, array $authzGroups)
    {
        $authzGroupsPrivileges = !is_string($authzGroupsPrivileges) ? $authzGroupsPrivileges : [$authzGroupsPrivileges];
        
        foreach($authzGroupsPrivileges as $privilige) {
            $privileges[$privilige] = !empty($privileges[$privilige]) ? $privileges[$privilige] : [];
            $privileges[$privilige] = array_unique(array_merge($privileges[$privilige], $authzGroups));
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
