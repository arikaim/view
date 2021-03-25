<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\View\Html\Traits;

use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;

/**
 * Access options for view components
 */
trait Access
{
    /**
     * Gte cache service
     *
     * @return object|null
     */
    public function getAccessService()
    {
        return $this->accessService;
    }

    /**
     * Set cache service
     *
     * @param object $access
     * @return void
     */
    public function setAccessService($access)
    {
        $this->accessService = $access;
    }

    /**
     * Process component access option
     *
     * @param ComponentDescriptorInterface $component    
     * @return ComponentDescriptorInterface
     */
    protected function processAccessOption(ComponentDescriptorInterface $component)
    { 
        $access = $component->getOption('access');  
        if (empty($access) == true) {
            return $component;
        }

        // check access 
        if ($this->checkAuthOption($access) == false) {
            $component->setError('ACCESS_DENIED',['name' => $component->getFullName()]);    
            return $component;
        } 
        // check permissions
        if ($this->checkPermissionOption($access) == false) {
            $component->setError('ACCESS_DENIED',['name' => $component->getFullName()]);  
            return $component;                       
        }

        return $component;
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
        $provider = $this->getAccessService()->requireProvider($auth);
        if (\is_object($provider) == false) {
            return false;
        }

        return $this->getAccessService()->isLogged();  
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
        
        return $this->getAccessService()->hasAccess($permission);      
    }
}
