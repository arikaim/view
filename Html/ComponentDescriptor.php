<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View\Html;

use Arikaim\Core\Collection\Arrays;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Http\Url;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;

/**
 * Html component descriptor
 */
class ComponentDescriptor implements ComponentDescriptorInterface
{
    // component locations
    const UNKNOWN_COMPONENT   = 0;
    const TEMPLATE_COMPONENT  = 1; 
    const EXTENSION_COMPONENT = 2;
    const PRIMARY_TEMLATE     = 3;
    const COMPONENTS_LIBRARY  = 4; 

    /**
     * Component name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Component full name
     *
     * @var string
     */
    protected $fullName;

    /**
     * Template, extension or components library name
     *
     * @var string
     */
    protected $templateName = '';

    /**
     * Template url
     *
     * @var string
     */
    protected $templateUrl = '';

    /**
     * Component path
     *
     * @var string
     */
    protected $path = '';

    /**
     * Component Location
     *
     * @var integer
     */
    protected $location = Self::UNKNOWN_COMPONENT;  

    /**
     * Component full path
     *
     * @var string
     */
    protected $fullPath = '';

    /**
     * File path
     *
     * @var string
     */
    protected $filePath = '';

    /**
     * Language code
     *
     * @var string
     */
    protected $language = '';

    /**
     * Html code
     *
     * @var string
     */
    protected $htmlCode = '';

    /**
     * Component render error
     *
     * @var array|null
     */
    protected $error = null;

    /**
     * Base path
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Component files
     *
     * @var array
     */
    protected $files = [];

    /**
     * Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Optins file
     *
     * @var string
     */
    protected $optionsFile;

    /**
     * Properies
     *
     * @var array
     */
    protected $properties;

    /**
     * View path
     *
     * @var string
     */
    protected $viewPath = '';

    /**
     * Extensions path
     *
     * @var string
     */
    protected $extensionsPath = '';

    /**
     * Primary template name
     *
     * @var string
     */
    protected $primaryTemplate = '';

    /**
     * Component data file
     *
     * @var string|null
     */
    protected $dataFile = null;

    /**
     * Remove include options
     *
     * @var boolean
     */
    protected $removeIncludeOptions = false;

    /**
     * Component type
     *
     * @var string
     */
    protected $componentType = '';

    /**
     * Component context used in render 
     *
     * @var array
     */
    protected $context = [];

    /**
     *  Return true if compoent has html file
     */
    protected $hasHtmlContent = false;

    /**
     * Html file name
     *
     * @var string|null
     */
    protected $htmlFileName = null;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $basePath
     * @param string $language
     * @param string|null $optionsFile
     * @param string|null $viewPath
     * @param string|null $extensionsPath
     * @param string $primaryTemplate
     * @param string $componentType
     */
    public function __construct(
        string $name,
        string $basePath, 
        string $language = 'en',
        ?string $optionsFile = null,
        ?string $viewPath = null,
        ?string $extensionsPath = null,
        string $primaryTemplate,
        string $componentType
    ) 
    {
        $this->fullName = $name;
        $this->language = $language;
        $this->optionsFile = $optionsFile;
        $this->basePath = $basePath;
        $this->viewPath = $viewPath; 
        $this->extensionsPath = $extensionsPath;    
        $this->primaryTemplate = $primaryTemplate;
        $this->componentType = $componentType;
        $this->files = [
            'js'   => [],
            'css'  => []           
        ];
        $this->context = [];

        $this->parseName($this->fullName);
        $this->resolvePath();        
    }

    /**
     * Get context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set context
     *
     * @param array $context
     * @return void
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Get include file url
     *
     * @param string $fileType
     * @return string|null
     */
    public function getIncludeFile(string $fileType): ?string
    {
        $file = $this->getComponentFile($fileType);   

        return ($file !== false) ? $this->getFileUrl($file) : null;      
    }

    /**
     * Resolve component data
     *
     * @return void
     */
    public function resolve(): void
    {
        $this->resolvePropertiesFileName();
        $this->properties = $this->loadProperties();

        $this->resolveOptionsFileName();
        $this->options = $this->loadOptions(); 

        $this->addComponentFile('js');    
        $this->addComponentFile('css');           
        $this->resolveHtmlContent();
        
        $this->resolveDataFile();   
        
        if ($this->isValid() == false) {           
            $this->setError('TEMPLATE_COMPONENT_NOT_FOUND',['full_component_name' => $this->fullName]);             
        }
    } 

    
    /**
     * Get component data file.
     * 
     * @return string|null
     */
    public function getDataFile(): ?string
    {
        return $this->dataFile;
    }

    /**
     * Set primary template name
     *
     * @param string $name
     * @return void
     */
    public function setPrimaryTemplate(string $name): void
    {
        $this->primaryTemplate = $name;
    }

    /**
     * Get primary template
     *
     * @return string
     */
    public function getPrimaryTemplate(): string
    {
        return $this->primaryTemplate;
    }

    /**
     * Return true if component has child 
     *
     * @return boolean
     */
    public function hasParent(): bool
    {
        if (empty($this->path) == true) {
            return false;
        }
        $tokens = \explode('/',$this->path);

        return (\count($tokens) > 0);
    }

    /**
     * Return base path
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get component name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get component full name
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * Get template file
     * 
     * @param string|null $fileName
     * @return string
     */
    public function getTemplateFile(): string
    {
        return $this->filePath . $this->htmlFileName;
    }

    /**
     * Return true if have error
     *
     * @return boolean
     */
    public function hasError(): bool
    {
        return !empty($this->error);
    }

    /**
     * Return true if component have html content
     *    
     * @return boolean
     */
    public function hasContent(): bool
    {
        return $this->hasHtmlContent;
    }

    /**
     * Resolev html content
     *   
     * @return void
     */
    protected function resolveHtmlContent(): void
    {   
        $this->hasHtmlContent = (empty($this->htmlFileName) == false) ? \file_exists($this->fullPath . $this->htmlFileName) : false;
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
     * Return true if component have files
     *
     * @param string $fileType
     * @return boolean
     */
    public function hasFiles(?string $fileType = null): bool
    {
        if ($fileType == null) {
            return (isset($this->files[$fileType]) == true);
        }

        if (isset($this->files[$fileType]) == true) {
            return (\count($this->files[$fileType]) > 0);
        }

        return false;
    }

    /**
     * Return files 
     *
     * @param string $fileType
     * @return array
     */
    public function getFiles(?string $fileType = null): array
    {
        return (empty($fileType) == true) ? $this->files : $this->files[$fileType] ?? [];        
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
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Get full path
     *
     * @return string
     */
    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    /**
     * Get component type
     *
     * @return string
     */
    public function getComponentType(): string
    {
        return $this->componentType;
    }

    /**
     * Set component type
     *
     * @param string $type
     * @return void
     */
    public function setComponentType(string $type): void
    {
        $this->componentType = $type;
    }

    /**
     * Get location
     *
     * @return integer
     */
    public function getLocation(): int
    {
        return $this->location;
    }

    /**
     * Get template or extension name
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage(): string 
    {
        return $this->language;
    }

    /**
     * Get error
     *
     * @return array|null
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    /**
     * Get html code
     *
     * @return string
     */
    public function getHtmlCode(): string 
    {
        return $this->htmlCode;
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
     * Set html code
     *
     * @param string $code
     * @return void
     */
    public function setHtmlCode(string $code): void 
    {
        $this->htmlCode = $code;
    }

    /**
     * Set error
     *
     * @param string $code
     * @param array $params
     * @param string|null $msssage
     * @return void
     */
    public function setError(string $code, array $params = [], ?string $msssage = null): void 
    {
        $this->error = [
            'code'    => $code,
            'params'  => $params,
            'message' => $msssage
        ];
    }

    /**
     * Return true if component is valid
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        $content = 0;
        $content += ($this->hasContent() == true)    ?  1 : 0;
        $content += ($this->hasFiles('js') == true)  ?  1 : 0;
        $content += ($this->hasFiles('css') == true) ?  1 : 0;
        $content += ($this->hasProperties() == true) ?  1 : 0;

        return ($content > 0);
    }

    /**
     * Clear content
     *
     * @return void
     */
    public function clearContent(): void
    {
        $this->files = [
            'js'   => [],
            'css'  => []         
        ];
    }

    /**
     * Add component file
     *
     * @param string $fileExt
     * @return mixed
     */
    public function addComponentFile(string $fileExt)
    {
        $fileName = $this->name . '.' . $fileExt; 
        if (\file_exists($this->fullPath . $fileName) == false) {
            return false;
        }
        
        $file = [
            'file_name'      => $fileName,
            'path'           => $this->filePath,
            'full_path'      => $this->fullPath,
            'component_name' => $this->fullName,
            'component_type' => $this->componentType,
            'url'            => $this->getFileUrl($fileName) 
        ];

        return $this->addFile($file,$fileExt);       
    }

    /**
     * Add file
     *
     * @param array $file
     * @param string $fileType
     * @return void
     */
    public function addFile(array $file, string $fileType): void
    {
        $this->files[$fileType] = $this->files[$fileType] ?? [];

        \array_push($this->files[$fileType],$file);
    }

    /**
     * Parse component name 
     * 
     * @param string $name
     * @return void
     */
    protected function parseName(string $name): void
    {
        $nameSplit = \explode('/',$name);  
        $name = $nameSplit[0];
 
        if (\stripos($name,'::') !== false) {
            // extension component
            $tokens = \explode('::',$name);     
            $this->location = Self::EXTENSION_COMPONENT;
        } elseif (\stripos($name,'>') !== false) {
            // Primary template
            $tokens = \explode('>',$name);
            $this->location = Self::PRIMARY_TEMLATE;
        } elseif (\stripos($name,':') !== false) {
            // template component          
            $tokens = \explode(':',$name);  
            $this->location = Self::TEMPLATE_COMPONENT;
            if ($tokens[0] == 'components') {
                $tokens[0] = 'semantic';
                $this->location = Self::COMPONENTS_LIBRARY;
            } 
        } elseif (\stripos($name,'~') !== false) {
            // template component          
            $tokens = \explode('~',$name);  
            $this->location = Self::COMPONENTS_LIBRARY;      
        } else {
            // component location not set                         
            $this->location = Self::UNKNOWN_COMPONENT;     
            return;  
        }
  
        $this->path = \str_replace('.','/',$tokens[1]);
        $this->templateName = $tokens[0];          
        
        $path = \explode('/',$this->path);
        $this->name = \end($path);
        $this->htmlFileName = (empty($nameSplit[1]) == false) ? $nameSplit[1] . '.html' : $this->name . '.html';

        if ($this->location == Self::PRIMARY_TEMLATE) {
            // resolve component location (template or extension)
            $componentPath = $this->getComponentFullPath(Self::TEMPLATE_COMPONENT,$this->primaryTemplate);
            if (\file_exists($componentPath) == true) {               
                // primary template component
                $this->location = Self::TEMPLATE_COMPONENT;
                $this->templateName = $this->primaryTemplate;                
            } else {
                // set extension component
                $this->location = Self::EXTENSION_COMPONENT;               
            }                 
        }
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
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {       
        return (array)$this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl(): string
    {
        $path = (empty($this->path) == false) ? $this->path . '/' : '';
     
        return $this->templateUrl . '/' . $this->basePath . '/' . $path;
    }

    /**
     * Get root componetn path
     *
     * @param bool $relative
     * @return string
     */
    public function getRootPath(bool $relative = false): string
    {
        $tokens = \explode(DIRECTORY_SEPARATOR,$this->path);

        $path = (\count($tokens) <= 1) ? $this->path : $tokens[0];  
        $templatePath = $this->getTemplatePath($this->templateName,$this->location);

        return ($relative == true) ? $path : $templatePath . $this->basePath . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR;
    }

    /**
     * Get template path
     *
     * @param string $template
     * @param int $location  
     * @return string
     */
    public function getTemplatePath(string $template, int $location): string 
    {   
        switch($location) {
            case Self::EXTENSION_COMPONENT:
                return $this->extensionsPath . $template . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
            case Self::TEMPLATE_COMPONENT:
                return $this->viewPath . 'templates' . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR;
            case Self::COMPONENTS_LIBRARY:
                return $this->viewPath . 'components' . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR;
        }           
        
        return $this->viewPath;
    }

    /**
     * Get component file
     *
     * @param string $fileExt
     * @return string|false
     */
    public function getComponentFile(string $fileExt) 
    {                 
        $fileName = $this->name . '.' . $fileExt;     

        return \file_exists($this->fullPath . $fileName) ? $fileName : false;
    }

    /**
     * Convert file path to url
     *
     * @param string $fileName
     * @return string
     */
    public function getFileUrl(string $fileName): string
    {
        return $this->getUrl() . $fileName;
    }

    /**
     * Load properties json file
     *
     * @return array
     */
    public function loadProperties(): array
    {       
        $fileName = $this->getPropertiesFileName();

        return (empty($fileName) == true) ? [] : File::readJsonFile($fileName);                   
    }

    /**
     * Load options json file
     *
     * @return array
     */
    public function loadOptions(): array
    {         
        $optionsFile = $this->getOptionsFileName();
        $data = (empty($optionsFile) == false) ? File::readJsonFile($optionsFile) : [];
                 
        if (($this->removeIncludeOptions == true) && (isset($data['include']) == true)) {
            unset($data['include']);
        }

        return $data;    
    }

    /**
     * Get component full path
     *
     * @param integer $location
     * @return string
     */
    public function getComponentFullPath(int $location, string $templateName): string
    {
        if ($location == Self::COMPONENTS_LIBRARY)  {
            return $this->getTemplatePath($templateName,$location) . $this->path . DIRECTORY_SEPARATOR; 
        } 
        return $this->getTemplatePath($templateName,$location) . $this->basePath . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR;     
    }

    /**
     * Resolve component data file
     *
     * @return void
     */
    protected function resolveDataFile(): void
    {
        $fileName = $this->fullPath . $this->name . '.php';
       
        $this->dataFile = (\file_exists($fileName) == true) ? $fileName : null;       
    }

    /**
     * Resolve component path
     *
     * @return void
     */
    protected function resolvePath(): void 
    {
        $this->fullPath = $this->getComponentFullPath($this->location,$this->templateName);

        $path = $this->basePath . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR;   
        $templatePath = DIRECTORY_SEPARATOR . $this->templateName . DIRECTORY_SEPARATOR;

        switch($this->location) {          
            case Self::TEMPLATE_COMPONENT:
                $this->filePath = $templatePath . $path;
                $this->templateUrl = Url::getTemplateUrl($this->templateName);
                break;
            case Self::COMPONENTS_LIBRARY:         
                $this->filePath = $templatePath . $this->path . DIRECTORY_SEPARATOR;    
                $this->templateUrl = Url::getComponentsLibraryUrl($this->templateName);            
                break;
            case Self::EXTENSION_COMPONENT:
                $this->filePath = $templatePath . 'view' . DIRECTORY_SEPARATOR . $path;    
                $this->templateUrl = Url::getExtensionViewUrl($this->templateName);        
            break;
        }
    }

    /**
     * Get template url
     *
     * @return string
     */
    public function getTemplateUrl(): string
    {
        return $this->templateUrl; 
    }

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
