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
use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Http\Url;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;

/**
 * Html component descriptor
 */
class ComponentDescriptor implements ComponentDescriptorInterface
{
    const UNKNOWN_COMPONENT   = 0;
    const TEMPLATE_COMPONENT  = 1; 
    const EXTENSION_COMPONENT = 2;
    const GLOBAL_COMPONENT    = 3; 
    const PRIMARY_TEMLATE     = 4;
    
    /**
     * Component name
     *
     * @var string
     */
    protected $name;

    /**
     * Component full name
     *
     * @var string
     */
    protected $fullName;

    /**
     * Template or extension name
     *
     * @var string
     */
    protected $templateName;

    /**
     * Component path
     *
     * @var string
     */
    protected $path;

    /**
     * Type
     *
     * @var integer
     */
    protected $type;  

    /**
     * Component full path
     *
     * @var string
     */
    protected $fullPath;

    /**
     * File path
     *
     * @var string
     */
    protected $filePath;

    /**
     * Language code
     *
     * @var string
     */
    protected $language;

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
    protected $basePath;

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
    protected $viewPath;

    /**
     * Extensions path
     *
     * @var string
     */
    protected $extensionsPath;

    /**
     * Component name selector type
     *
     * @var string|null
     */
    protected $selectorType = null;

    /**
     * Primary template name
     *
     * @var string|null
     */
    protected $primaryTemplate;

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
    private $removeIncludeOptions = false;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $basePath
     * @param string $language
     * @param string|null $optionsFile
     * @param string|null $viewPath
     * @param string|null $extensionsPath
     * @param string|null $primaryTemplate
     */
    public function __construct(
        string $name,
        string $basePath, 
        string $language = 'en',
        ?string $optionsFile = null,
        ?string $viewPath = null,
        ?string $extensionsPath = null,
        ?string $primaryTemplate = null) 
    {
        $this->fullName = $name;
        $this->language = $language;
        $this->optionsFile = $optionsFile;
        $this->basePath = $basePath;
        $this->viewPath = $viewPath; 
        $this->extensionsPath = $extensionsPath;    
        $this->primaryTemplate = $primaryTemplate;

        $this->clearContent();
        $this->parseName($name);
        $this->resolvePath();
        $this->resolvePropertiesFileName();
        $this->resolveOptionsFileName();
        $this->resolveComponentFiles();

        $this->properties = $this->loadProperties();
        $this->options = $this->loadOptions(); 

        $this->resolveDataFile();
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
     * @return string|null
     */
    public function getPrimaryTemplate(): ?string
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
     * Get parent component name
     *
     * @return string
     */
    public function getParentName(): string
    {
        $tokens = \explode('/',$this->path);
        $count = \count($tokens) - 1;
        $path = $tokens[$count] ?? '';

        return $this->templateName . $this->selectorType . $path;
    }

    /**
     * Get root component name
     *
     * @return string
     */
    public function getRootName(): string
    {
        $tokens = \explode('/',$this->path);

        return $this->templateName . $this->selectorType . $tokens[0];
    }

    /**
     * Create component
     *
     * @param string|null $name If name is null parent component name is used
     * @return ComponentDescriptorInterface|null
     */
    public function createComponent(?string $name = null)
    {
        if ($this->hasParent() == false) {
            return false;
        }
        $name = (empty($name) == true) ? $this->getParentName() : $name;
        $child = new Self($name,$this->basePath,$this->language,$this->optionsFile,$this->viewPath,$this->extensionsPath);

        return (\is_object($child) == true) ? $child : null;
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
     * @return string|null
     */
    public function getTemplateFile(): ?string
    {
        switch($this->type) {
            case Self::EXTENSION_COMPONENT: 
                $path = $this->templateName . DIRECTORY_SEPARATOR . 'view';
                break;
            case Self::TEMPLATE_COMPONENT: 
                $path = '';
                break;
            case Self::GLOBAL_COMPONENT: 
                $path = '';
                break;
            case Self::UNKNOWN_COMPONENT: 
                return null;
        }  
        if (isset($this->files['html'][0]['file_name']) == true) {
            return $path . $this->filePath . $this->files['html'][0]['file_name'];
        }

        return null;
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
        return (empty($this->getTemplateFile()) == false);     
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
        if ($fileType == null) {
            return $this->files;
        }

        return (array)$this->files[$fileType] ?? [];          
    }

    /**
     * Get properties
     * 
     * @param array $default
     * @return array
     */
    public function getProperties(array $default = []): array
    {
        return (\is_array($this->properties) == true) ? $this->properties : $default;
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
     * Get type
     *
     * @return integer
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Get template or extension name
     *
     * @return string
     */
    public function getTemplateName(): ?string
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
        $option = Arrays::getValue($this->options,$path);

        return (empty($option) == true) ? $default : $option;          
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
            'css'  => [],
            'html' => []
        ];
    }

    /**
     * Add files
     *
     * @param array $files
     * @param string $fileType
     * @param string|null $sourceComponentName
     * @return bool
     */
    public function addFiles(array $files, string $fileType, ?string $sourceComponentName): bool
    {
        $this->files[$fileType] = $this->files[$fileType] ?? [];
    
        foreach ($files as $file) {
            if (empty($sourceComponentName) == false) {
                $item['source_component'] = $sourceComponentName;
            }
            if (empty($file) == false) {
                \array_unshift($this->files[$fileType],$file); 
            }              
        }
        
        return true;            
    }

    /**
     * Add component file
     *
     * @param string $fileExt
     * @return mixed
     */
    public function addComponentFile(string $fileExt)
    {
        $fileName = $this->getComponentFile($fileExt);      
        if ($fileName === false) {
            return false;
        }
        $file = [
            'file_name' => $fileName,
            'path'      => $this->filePath,
            'full_path' => $this->getFullPath(),
            'url'       => $this->getFileUrl($fileName) 
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
    public function addFile(array $file, string $fileType)
    {
        $this->files[$fileType] = $this->files[$fileType] ?? [];

        \array_push($this->files[$fileType],$file);
    }

    /**
     * Parse component name 
     *  [extesnon name | template name]:[name path]
     *  for current template  [name path]
     *  [extenstion name] :: [name path]
     * 
     * @param string $name
     * @return void
     */
    protected function parseName(string $name): void
    {
        if (\stripos($name,'::') !== false) {
            // extension component
            $tokens = \explode('::',$name);     
            $type = Self::EXTENSION_COMPONENT;
            $this->selectorType = '::';
        } elseif (\stripos($name,'>') !== false) {
            // resolve location
            $tokens = \explode('>',$name);
            $type = Self::PRIMARY_TEMLATE;
            $this->selectorType = '>';
        } else {
            // template component
            $this->selectorType = ':';
            $tokens = \explode(':',$name);  
            $type = ($tokens[0] == 'components') ? Self::GLOBAL_COMPONENT : Self::TEMPLATE_COMPONENT;    
        }

        if (isset($tokens[1]) == false) {    
            // component location not set                     
            $this->path = \str_replace('.','/',$tokens[0]);            
            $this->templateName = null;
            $type = Self::UNKNOWN_COMPONENT;        
        } else {
            $this->path = \str_replace('.','/',$tokens[1]);
            $this->templateName = $tokens[0];          
        }

        if ($type == Self::PRIMARY_TEMLATE) {
            $this->path = \str_replace('.','/',$tokens[1]);
            $this->templateName = $tokens[0]; 

            // resolve component location (template or extension)
            $templateName = (empty($this->primaryTemplate) == true) ? $this->templateName : $this->primaryTemplate;  
            $componentPath = $this->getComponentFullPath(Self::TEMPLATE_COMPONENT,$templateName);
            if (\file_exists($componentPath) == true) {               
                // primary template component
                $type = Self::TEMPLATE_COMPONENT;
                $this->templateName = $templateName; 
            } else {
                // set extension component
                $type = Self::EXTENSION_COMPONENT;
                $this->templateName = $tokens[0];
            }                 
        }

        $this->type = $type;
        $parts = \explode('/',$this->path);
        $this->name = \end($parts);
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
     * Init component from array
     *
     * @param array $componentData
     * @return ComponentDescriptor
     */
    public static function createFromArray(array $data)
    {
        $component = new Self($data['name'],$data['basePath']);
        foreach($data as $key => $value) {
            $component->{$key} = $value;
        }

        return $component;
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
     * Get template url
     *
     * @return string|null
     */
    public function getTemplateUrl(): ?string
    {
        switch ($this->type) {
            case Self::TEMPLATE_COMPONENT:
                return Url::getTemplateUrl($this->templateName);
                
            case Self::EXTENSION_COMPONENT:
                return Url::getExtensionViewUrl($this->templateName);
               
            case Self::GLOBAL_COMPONENT:
                return Url::VIEW_URL;
            default: 
                return null;            
        }       
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->getTemplateUrl() . '/' . $this->basePath . '/' . $this->path . '/';
    }

    /**
     * Return root component name
     *
     * @return string
     */
    public function getRootComponentPath(): string
    {
        return Self::getTemplatePath($this->templateName,$this->type,$this->viewPath,$this->extensionsPath);
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
        
        return ($relative == true) ? $path : $this->getRootComponentPath() . $this->basePath . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR;
    }

    /**
     * Get template path
     *
     * @param string $template
     * @param int $type
     * @param string $viewPath
     * @param string $extensionsPath
     * @return string|null
     */
    public static function getTemplatePath(string $template, int $type, $viewPath, $extensionsPath): ?string 
    {   
        switch($type) {
            case Self::EXTENSION_COMPONENT:
                return $extensionsPath . $template . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

            case Self::TEMPLATE_COMPONENT:
                return $viewPath . 'templates' . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR;

            case Self::GLOBAL_COMPONENT:
                return $viewPath;
        }           
        
        return null;
    }

    /**
     * Get component path
     *
     * @return string|null
     */
    public function getComponentPath(): ?string
    {
        return Self::getTemplatePath($this->templateName,$this->type,$this->viewPath,$this->extensionsPath);
    }

    /**
     * Get component file
     *
     * @param string $fileExt
     * @param string $language
     * @return string|false
     */
    public function getComponentFile(string $fileExt = 'html', string $language = '') 
    {         
        if ($fileExt == 'json') {
            $fileName = $this->getName() . $language . '.' . $fileExt;
            $fullFileName = $this->getFullPath() . $fileName;
        } else {
            $fileName = $this->getName() . '.' . $fileExt;
            $fullFileName = $this->getFullPath() . $fileName;              
        }
       
        return \file_exists($fullFileName) ? $fileName : false;
    }

    /**
     * Convert file path to url
     *
     * @param string $fileName
     * @return string
     */
    public function getFileUrl(?string $fileName): string
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
     * @param integer $type
     * @return string
     */
    public function getComponentFullPath(int $type, ?string $templateName = null): string
    {
        $templateName = $templateName ?? $this->templateName;
        $templateFullPath = Self::getTemplatePath($templateName,$type,$this->viewPath,$this->extensionsPath); 
        
        $basePath = $this->basePath ?? '';
        $path = $basePath . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR;   
        
        return $templateFullPath . $path;     
    }

    /**
     * Resolve component data file
     *
     * @return void
     */
    protected function resolveDataFile(): void
    {
        $fileName = $this->fullPath . $this->getName() . '.php';
       
        $this->dataFile = (\file_exists($fileName) == true) ? $fileName : null;       
    }

    /**
     * Resolve component path
     *
     * @return void
     */
    protected function resolvePath(): void 
    {                 
        $basePath = (empty($this->basePath) == false) ? DIRECTORY_SEPARATOR . $this->basePath : '';
        $path = $basePath . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR;   
      
        switch($this->type) {
            case Self::EXTENSION_COMPONENT:
                $templatePath = '';
                break;
            case Self::TEMPLATE_COMPONENT:
                $templatePath = $this->templateName . DIRECTORY_SEPARATOR;
                break;
            case Self::GLOBAL_COMPONENT:
                $templatePath = '';               
                $path = $this->path . DIRECTORY_SEPARATOR; 
                break;
            default:
                $templatePath = '';      
        }
        $this->fullPath = $this->getComponentFullPath($this->type);
        $this->filePath = \rtrim($templatePath,DIRECTORY_SEPARATOR) . $path;
    }

    /**
     * Resolve properties file name
     *
     * @return void
     */
    private function resolvePropertiesFileName(): void
    {
        $language = ($this->language != 'en') ? '-' . $this->language : '';
        $fileName = $this->getComponentFile('json',$language);

        if ($fileName === false) {
            $fileName = $this->getComponentFile('json');
            if ($fileName === false) {
                return;
            }
        } 
        $this->setPropertiesFileName($this->getFullPath() . $fileName);   
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

    /**
     * Resolve component files
     *
     * @return void
     */
    private function resolveComponentFiles(): void
    {
        // js files
        $this->addComponentFile('js');
        // css file
        $this->addComponentFile('css');
        // html file
        $this->addComponentFile('html');        
    }
}
