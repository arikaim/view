<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\View\Traits;

/**
 * Access options for view components
 */
trait Access
{
    /**
     * Check component access option
     *
     * @param $component    
     * @return bool
     */
    protected function checkAccessOption($component): bool
    { 
        $access = $component->getOption('access');  
        if (empty($access) == true) {
            return true;
        }

        // check access 
        if ($this->checkAuthOption($access) == false) {
           return false;
        } 
        // check permissions
        if ($this->checkPermissionOption($access) == false) {
            return false;                   
        }

        return true;
    }

    /**
     * Check auth and permissions access
     *
     * @param array $accessOptions       
     * @return boolean
     */
    public function checkAuthOption(array $accessOptions): bool
    {
        $auth = $accessOptions['auth'] ?? null;
        if ((\strtolower($auth) == 'none') || (empty($auth) == true)) {
            return true;
        }

        // add auth provider
        $provider = $this->getService('access')->requireProvider($auth);
        if (\is_object($provider) == false) {
            return false;
        }

        return $this->getService('access')->isLogged();  
    }

    /**
     * Check auth and permissions access
     *
     * @param array $accessOptions   
     * @return boolean
     */
    public function checkPermissionOption(array $accessOptions): bool
    {
        $permission = $accessOptions['permission'] ?? null;

        if ((\strtolower($permission) == 'none') || (empty($permission) == true)) {
            return true;
        }
        
        return $this->getService('access')->hasAccess($permission);      
    }
}
