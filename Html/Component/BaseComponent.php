<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View\Html\Component;

use Arikaim\Core\Http\Url;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;

/**
 * Base component
 */
class BaseComponent 
{
    const NOT_VALID_COMPONENT_ERROR = 'Not valid component';
    const ACESS_DENIED_ERROR        = 'Access denied for component';
    const NOT_FOUND_ERROR           = 'Component not found';
    const COMPONENT_ERROR_NAME      = 'components:message.error';

    /**
     * Errors messages
     *
     * @var array
     */
    protected static $errors = [
        'NOT_VALID_COMPONENT'          => Self::NOT_VALID_COMPONENT_ERROR,
        'TEMPLATE_COMPONENT_NOT_FOUND' => Self::NOT_FOUND_ERROR,
        'ACCESS_DENIED'                => Self::ACESS_DENIED_ERROR
    ];

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
     * @param string $viewPath
     * @param string $extensionsPath
     * @param string $primaryTemplate
     * @param string $componentType
     */
    public function __construct(
        string $name,
        string $basePath, 
        string $language,        
        string $viewPath,
        string $extensionsPath,
        string $primaryTemplate,
        string $componentType
    ) 
    {
        $this->fullName = $name;
        $this->language = $language;      
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

        $this->init();
    }

    /**
     * Init component
     *
     * @return void
     */
    public function init(): void 
    {
    }
    
    /**
     * Resolve component
     *
     * @param array $params
     * @return bool
     */
    public function resolve(array $params = []): bool
    {
        return false;
    } 

    /**
     * Create component
     *
     * @param string $name
     * @param string $language
     * @return mixed
     */
    public function create(string $name, string $language)
    {
        return new Self(
            $name,
            $this->basePath,
            $language,
            $this->viewPath,
            $this->extensionsPath,
            $this->primaryTemplate,
            $this->componentType);
    }

    /**
     * Get default prams
     *
     * @param ComponentDescriptorInterface $component
     * @param array $params
     * @return array
     */
    protected function getDefultParams(): array
    {
        // default params           
        return [
            'component_url'    => $this->getUrl(),
            'template_url'     => $this->getTemplateUrl(),
            'current_language' => $this->getLanguage()
        ];
    }

    /**
     * Get error message
     *
     * @param string $code
     * @param string $name
     * @return string
     */
    public function getErrorText(string $code, string $name = ''): string
    {
        $error = Self::$errors[$code] ?? $code;

        return $error . ' ' . $name;
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
     * Merge context
     *
     * @param array $data
     * @return void
     */
    public function mergeContext(array $data)
    {
        $this->context = \array_merge_recursive($this->context,$data);  
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
      
        return ($content > 0);
    }

    /**
     * Clear content
     *
     * @return void
     */
    public function clearContent(): void
    {
        $this->htmlCode = '';
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
     
        return ($this->location == Self::COMPONENTS_LIBRARY) ? $this->templateUrl . '/' . $path : $this->templateUrl . '/' . $this->basePath . '/' . $path;
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
}
