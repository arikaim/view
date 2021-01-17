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
     * @param string $language
     * @return string|false
     */
    public function getComponentFile(string $fileExt = 'html', string $language = '');
    
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
     * @param string|null $name
     * @return void
     */
    public function setPrimaryTemplate(?string $name): void;
    
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
     * @param array $default
     * @return array
     */
    public function getProperties(array $default = []): array;

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
     * Get component type
     *
     * @return integer
     */
    public function getType(): int;

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
     * Create component
     *
     * @param string|null $name If name is null parent component name is used
     * @return ComponentDescriptorInterface|null
    */
    public function createComponent(?string $name = null);

    /**
     * Add files
     *
     * @param array $files
     * @param string $fileType
     * @return bool
     */
    public function addFiles(array $files, string $fileType): bool;

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
     * Get root component name
     *
     * @return string
     */
    public function getRootName(): string;

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
     * @return string|null
     */
    public function getTemplateUrl(): ?string;

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
     * Return root component name
     *
     * @return string
     */
    public function getRootComponentPath(): string;

    /**
     * Return base path
     *
     * @return string
     */
    public function getBasePath(): string;    
}
