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
use Arikaim\Core\Utils\File;

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
     * Process component options
     *  
     * @return void
     */
    protected function processOptions(): void
    {               
    }

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
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $path, $default = null)
    {
        return $this->options[$path] ?? $default;       
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
        $optionsFile = $this->getOptionsFileName();
        if (empty($optionsFile) == true) {
            return;
        }

        $options = File::readJsonFile($optionsFile);                 
        if (($this->removeIncludeOptions == true) && (isset($data['include']) == true)) {
            unset($data['include']);
        }

        $this->options = $options;          
    }

    /**
     * Get options file name
     *
     * @return string|null
     */
    public function getOptionsFileName(): ?string
    {
        return $this->files['options']['file_name'] ?? null;         
    }

    /**
     * Set options file name
     *
     * @param string $fileName
     * @return void
     */
    public function setOptionsFileName(string $fileName): void
    {
        $this->files['options']['file_name'] = $fileName;
    }

    /**
     * Resolve options file name
     *
     * @param string|null $path  
     * @return void
     */
    private function resolveOptionsFileName(?string $path = null): void
    {   
        $path = $path ?? $this->getFullPath();
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
