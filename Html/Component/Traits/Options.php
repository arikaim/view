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

use Arikaim\Core\Collection\Arrays;

/**
 * Component options
 */
trait Options
{
    /**
     * Remove include options
     *
     * @var boolean
     */
    protected $removeIncludeOptions = false;

    /**
     * Optins file
     *
     * @var string
     */
    protected $optionsFile = 'component.json';

    /**
     * Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Component type option
     *
     * @return void
     */
    protected function componentTypeOption(): void
    { 
        // component type option
        $componentType = $this->getOption('component-type');
        if (empty($componentType) == false) {
            $this->setComponentType($componentType);
        } 
    }

    /**
     * Set option file name
     *
     * @param string $name  
     * @return void
     */
    public function setOptionFile(string $name): void
    {
        $this->optionsFile = $name;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get option
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;       
    }

    /**
     * Set option value
     *
     * @param string $path
     * @param mixed $value
     * @return void
     */
    public function setOption(string $path, $value): void
    {
        $this->options = Arrays::setValue($this->options,$path,$value);       
    }

    /**
     * Load options json file
     *
     * @return void
     */
    public function loadOptions(): void
    {         
        $this->resolveOptionsFileName();
        $optionsFile = $this->getOptionsFileName();

        if (empty($optionsFile) == true) {
            return;
        }

        $json = \file_get_contents($optionsFile);
        $options = \json_decode($json,true);
               
        if (($this->removeIncludeOptions == true) && (isset($options['include']) == true)) {
            unset($options['include']);
        }

        $this->options = $options;    
    }

    /**
     * Get options file name
     *
     * @return string|null
     */
    protected function getOptionsFileName(): ?string
    {
        return $this->files['options']['file_name'] ?? null;         
    }

    /**
     * Set options file name
     *
     * @param string $fileName
     * @return void
     */
    protected function setOptionsFileName(string $fileName): void
    {
        $this->files['options']['file_name'] = $fileName;
    }

    /**
     * Resolve options file name
     *
     * @param string|null $path  
     * @return void
     */
    protected function resolveOptionsFileName(?string $path = null): void
    {   
        $path = $path ?? $this->fullPath;
        $fileName = $path . $this->optionsFile;

        if (\file_exists($fileName) == true) {
            $this->setOptionsFileName($fileName);
            return;
        }

        // Check for root component options file             
        $fileName = $this->getRootPath() . $this->optionsFile;
        if (\file_exists($fileName) == true) {
            // disable includes from parent component     
            $this->removeIncludeOptions = true;
            $this->setOptionsFileName($fileName);
        }        
    }    
}
