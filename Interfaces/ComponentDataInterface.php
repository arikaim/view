<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View\Interfaces;

/**
 * Html component data interface
 */
interface ComponentDataInterface 
{  
    /**
     * Return true if component have error
     *
     * @return boolean
     */
    public function hasError();

    /**
     * Return true if component is not empty
     *
     * @return boolean
     */
    public function hasContent();
 
    /**
     * Return component files 
     *
     * @param string $fileType
     * @return array
     */
    public function getFiles($fileType = null);

    /**
     * Get properties
     *
     * @return void
     */
    public function getProperties();

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Get component type
     *
     * @return integer
     */
    public function getType();

    /**
     * Check if component is valid 
     *
     * @return boolean
     */
    public function isValid();

    /**
     * Get component html code
     *
     * @return string
     */
    public function getHtmlCode();

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getUrl();

    /**
     * Return true if component has parent component 
     *
     * @return boolean
     */
    public function hasParent();

    /**
     * Create component
     *
     * @param string|null $name If name is null parent component name is used
     * @return ComponentDataInterface|false
    */
    public function createComponent($name = null);

    /**
     * Add files
     *
     * @param string|array $files
     * @param string $fileType
     * @return bool
     */
    public function addFiles($files, $fileType);

    /**
     * Get option
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getOption($name, $default = null);

    /**
     * Get template file
     *
     * @return string|false
     */
    public function getTemplateFile();

    /**
     * Set html code
     *
     * @param string $code
     * @return void
     */
    public function setHtmlCode($code);

    /**
     * Get root component name
     *
     * @return string
     */
    public function getRootName();

    /**
     * Set error
     *
     * @param string $code
     * @param array $params
     * @param string|null $msssage
     * @return void
     */
    public function setError($code, $params = [], $msssage = null);
}
