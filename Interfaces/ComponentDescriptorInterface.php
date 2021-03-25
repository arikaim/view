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
interface ComponentDescriptorInterface 
{  
    // component types
    const ARIKAIM_COMPONENT_TYPE = 'arikaim';
    const VUE_COMPONENT_TYPE     = 'vue';
    const REACT_COMPONENT_TYPE   = 'react';
    const STATIC_COMPONENT_TYPE  = 'static';
    const EMAIL_COMPONENT_TYPE   = 'email';
    const SVG_COMPONENT_TYPE     = 'svg';
    const JSON_COMPONENT_TYPE    = 'json';

    /**
     * Set context
     *
     * @param array $context
     * @return void
     */
    public function setContext(array $context): void;

    /**
     * Get context
     *
     * @return array
     */
    public function getContext(): array;

    /**
     * Get include file url
     *
     * @param string $fileType
     * @return string|null
     */
    public function getIncludeFile(string $fileType): ?string;

    /**
     * Add file
     *
     * @param array $file
     * @param string $fileType
     * @return void
     */
    public function addFile(array $file, string $fileType): void;

    /**
     * Resolve component data
     *
     * @return void
     */
    public function resolve(): void;

    /**
     * Get component data file.
     * 
     * @return string|null
     */
    public function getDataFile(): ?string;

    /**
     * Get component file
     *
     * @param string $fileExt  
     * @return string|false
     */
    public function getComponentFile(string $fileExt);
    
    /**
     * Get full path
     *
     * @return string
     */
    public function getFullPath(): string;

    /**
     * Convert to array
     *
     * @return array
    */
    public function toArray(): array;

    /**
     * Set primary template name
     *
     * @param string $name
     * @return void
     */
    public function setPrimaryTemplate(string $name): void;
    
    /**
     * Get language code
     *
     * @return string
     */
    public function getLanguage(): string;

    /**
     * Get error
     *
     * @return array|null
     */
    public function getError(): ?array;

    /**
     * Return true if component have error
     *
     * @return boolean
     */
    public function hasError(): bool;

    /**
     * Return true if component is not empty
     *
     * @return boolean
     */
    public function hasContent(): bool;
 
    /**
     * Return component files 
     *
     * @param string $fileType
     * @return array
     */
    public function getFiles(?string $fileType = null): array;

    /**
     * Get properties
     *   
     * @return array
     */
    public function getProperties(): array;

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get component location
     *
     * @return integer
     */
    public function getLocation(): int;

    /**
     * Set component type
     *
     * @param string $type
     * @return void
     */
    public function setComponentType(string $type): void;

    /**
     * Get component type
     *
     * @return string
     */
    public function getComponentType(): string;

    /**
     * Check if component is valid 
     *
     * @return boolean
     */
    public function isValid(): bool;

    /**
     * Get component html code
     *
     * @return string
     */
    public function getHtmlCode(): string;

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Return true if component has parent component 
     *
     * @return boolean
     */
    public function hasParent(): bool;

    /**
     * Get option
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $name, $default = null);

    /**
     * Set option value
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setOption(string $name, $value): void;
    
    /**
     * Get template file
     *
     * @return string|false
     */
    public function getTemplateFile(): ?string;

    /**
     * Set html code
     *
     * @param string $code
     * @return void
     */
    public function setHtmlCode(string $code): void;

    /**
     * Set error
     *
     * @param string $code
     * @param array $params
     * @param string|null $msssage
     * @return void
     */
    public function setError(string $code, array $params = [], ?string $msssage = null): void;

    /**
     * Clear content
     *
     * @return void
     */
    public function clearContent(): void;    

    /**
     * Get template url
     *
     * @return string
     */
    public function getTemplateUrl(): string;

    /**
     * Get component full name
     *
     * @return string
     */
    public function getFullName(): ?string;

    /**
     * Load properties json file
     *
     * @return array
     */
    public function loadProperties(): array;

    /**
     * Get template or extension name
     *
     * @return string|null
     */
    public function getTemplateName(): ?string ;

    /**
     * Return base path
     *
     * @return string
     */
    public function getBasePath(): string;    
}
