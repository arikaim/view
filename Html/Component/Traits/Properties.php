<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\View\Html\Component\Traits;

/**
 * Component properties
 */
trait Properties
{
    /**
     * Properies
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Resolve properties file name
     *
     * @return void
    */
    private function resolvePropertiesFileName(): void
    {
        if ($this->language != 'en') {
            $fileName = $this->name . '-' . $this->language . '.json';
            if (\file_exists($this->fullPath . $fileName) == true) {
                $this->setPropertiesFileName($this->fullPath . $fileName);   
                return;
            }          
        }

        $fileName = $this->name . '.json';
        if (\file_exists($this->fullPath . $fileName) == true) {
            $this->setPropertiesFileName($this->fullPath . $fileName);   
        }      
    }

    /**
     * Load properties json file
     *
     * @return void
     */
    public function loadProperties(): void
    {       
        $this->resolvePropertiesFileName();
        $fileName = $this->getPropertiesFileName();
        $this->properties = [];
        
        if (empty($fileName) == false) {
            $json = \file_get_contents($fileName);
            $properties = \json_decode($json,true);   
            $this->properties = (\is_array($properties) == true) ? $properties : [];           
        }                 
    }

    /**
     * Add properties to context array
     *
     * @return void
     */
    public function mergeProperties()
    {
        $this->context = \array_merge($this->context,$this->properties);
    }

    /**
     * Return true if component have properties
     *
     * @return boolean
     */
    public function hasProperties(): bool
    {
        if (isset($this->files['properties']) == true) {
            return (\count($this->files['properties']) > 0);
        }

        return false;
    }

    /**
     * Get properties
     *     
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Get properties file name
     *
     * @return string|null
    */
    public function getPropertiesFileName(): ?string 
    {
        return $this->files['properties']['file_name'] ?? null;       
    }

    /**
     * Set properties file name
     *
     * @param string $fileName
     * @return void
     */
    public function setPropertiesFileName(string $fileName): void 
    { 
        $this->files['properties']['file_name'] = $fileName;          
    }
}
