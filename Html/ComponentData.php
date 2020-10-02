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
use Arikaim\Core\View\Interfaces\ComponentDataInterface;

/**
 * Html component data
 */
class ComponentData implements ComponentDataInterface
{
    const UNKNOWN_COMPONENT   = 0;
    const TEMPLATE_COMPONENT  = 1; 
    const EXTENSION_COMPONENT = 2;
    const GLOBAL_COMPONENT    = 3; 
    const PRIMARY_TEMLATE     = 4;
    
    const DEFAULT_CSS_FRAMEWORK = 'fomantic';

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
    protected $htmlCode;

    /**
     * Component render error
     *
     * @var array
     */
    protected $error;

    /**
     * Base path
     *
     * @var string
     */
    protected $basePath;

    /**
     * UI framework name
     *
     * @var string|null
     */
    protected $framework;

    /**
     * Component files
     *
     * @var array
     */
    protected $files;

    /**
     * Options
     *
     * @var array
     */
    protected $options;

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
     * Default ui framework
     *
     * @var string
     */
    protected $defaultFramework;

    /**
     * Component name selector type
     *
     * @var string|null
     */
    protected $selectorType;

    /**
     * Primary template name
     *
     * @var string
     */
    protected $primaryTemplate;

    /**
     * Framework path
     *
     * @var string
     */
    protected $frameworkPath;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $basePath
     * @param string $language
     * @param string'null $optionsFile
     * @param string|null $viewPath
     * @param string|null $extensionsPath
     * @param string $defaultFramework
     * @param string|null $framework
     * @param string|null $primaryTemplate
     */
    public function __construct(
        $name,
        $basePath, 
        $language = 'en',
        $optionsFile = null,
        $viewPath = null,
        $extensionsPath = null,
        $defaultFramework = Self::DEFAULT_CSS_FRAMEWORK,
        $framework = null,
        $primaryTemplate = null) 
    {
        $this->selectorType = null;
        $this->fullName = $name;
        $this->language = $language;
        $this->optionsFile = $optionsFile;
        $this->basePath = $basePath;
        $this->viewPath = $viewPath; 
        $this->extensionsPath = $extensionsPath; 
        $this->error = null;
        $this->files['js'] = [];
        $this->files['css'] = [];
        $this->htmlCode = '';
        $this->defaultFramework = $defaultFramework;
        $this->primaryTemplate = $primaryTemplate;
        $this->parseName($name);
        $this->resolvePath();
        
        $this->framework = (empty($framework) == true) ? $this->defaultFramework : $framework;
        $this->frameworkPath = $this->getFrameworkPath();

        $this->resolvePropertiesFileName();
        $this->resolveOptionsFileName();
        $this->resolveComponentFiles();

        $this->properties = $this->loadProperties();
        $this->options = $this->loadOptions(); 
    }

    /**
     * Set primary template name
     *
     * @param string $name
     * @return void
     */
    public function setPrimaryTemplate($name)
    {
        $this->primaryTemplate = $name;
    }

    /**
     * Set css framework name
     *
     * @param string $framework
     * @return void
     */
    public function setFramework($framework)
    {
        $this->framework = $framework;
    }

    /**
     * Get css framework name
     *
     * @return string
     */
    public function getFramework()
    {
        return $this->framework;
    }

    /**
     * Return true if component has child 
     *
     * @return boolean
     */
    public function hasParent()
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
    public function getParentName()
    {
        $tokens = \explode('/',$this->path);
        $count = \count($tokens) - 1;
        $path = $tokens[$count];

        return $this->templateName . $this->selectorType . $path;
    }

    /**
     * Get root component name
     *
     * @return string
     */
    public function getRootName()
    {
        $tokens = \explode('/',$this->path);

        return $this->templateName . $this->selectorType . $tokens[0];
    }

    /**
     * Create component
     *
     * @param string|null $name If name is null parent component name is used
     * @return ComponentDataInterface|false
     */
    public function createComponent($name = null)
    {
        if ($this->hasParent() == false) {
            return false;
        }
        $name = (empty($name) == true) ? $this->getParentName() : $name;
        $child = new Self($name,$this->basePath,$this->language,$this->optionsFile,$this->viewPath,$this->extensionsPath,$this->defaultFramework,$this->framework);

        return (\is_object($child) == true) ? $child : false;
    }

    /**
     * Return base path
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Get component name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get component full name
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Get template file
     * 
     * @param string|null $frameweork
     * @return string|false
     */
    public function getTemplateFile($frameweork = null)
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
                return false;
        }  
        if (isset($this->files['html'][0]['file_name']) == true) {
            $frameworkPath = (empty($frameweork) == false) ? $this->getFrameworkPath() : '';
            return $path . $this->filePath . $frameworkPath . $this->files['html'][0]['file_name'];
        }

        return false;
    }

    /**
     * Return true if have error
     *
     * @return boolean
     */
    public function hasError()
    {
        return !empty($this->error);
    }

    /**
     * Return true if component have html content
     *
     * @return boolean
     */
    public function hasContent()
    {
        return ($this->getTemplateFile() == false) ? false : true;          
    }

    /**
     * Return true if component have properties
     *
     * @return boolean
     */
    public function hasProperties()
    {
        if (isset($this->files['properties']) == true) {
            return (\count($this->files['properties']) > 0) ? true : false;
        }

        return false;
    }

    /**
     * Return true if component have files
     *
     * @param string $fileType
     * @return boolean
     */
    public function hasFiles($fileType = null)
    {
        if ($fileType == null) {
            return (isset($this->files[$fileType]) == true) ? true: false;
        }

        if (isset($this->files[$fileType]) == true) {
            return (\count($this->files[$fileType]) > 0) ? true : false;
        }

        return false;
    }

    /**
     * Return files 
     *
     * @param string $fileType
     * @return array
     */
    public function getFiles($fileType = null)
    {
        if ($fileType == null) {
            return $this->files;
        }

        return (isset($this->files[$fileType]) == true) ? (array)$this->files[$fileType] : [];          
    }

    /**
     * Get properties
     * 
     * @param array $default
     * @return array
     */
    public function getProperties($default = [])
    {
        return (\is_array($this->properties) == true) ? $this->properties : $default;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get full path
     *
     * @return string
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get template or extension name
     *
     * @return string
     */
    public function getTemplateName()
    {
        return $this->templateName;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage() 
    {
        return $this->language;
    }

    /**
     * Get error
     *
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get html code
     *
     * @return string
     */
    public function getHtmlCode() 
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
    public function getOption($path, $default = null)
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
    public function setOption($path, $value)
    {
        $this->options = Arrays::setValue($this->options,$path,$value);       
    }

    /**
     * Set html code
     *
     * @param string $code
     * @return void
     */
    public function setHtmlCode($code) 
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
    public function setError($code, $params = [], $msssage = null) 
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
    public function isValid()
    {
        $content = 0;
        $content += ($this->hasContent() == true)    ?  1 : 0;
        $content += ($this->hasFiles('js') == true)  ?  1 : 0;
        $content += ($this->hasFiles('css') == true) ?  1 : 0;
        $content += ($this->hasProperties() == true) ?  1 : 0;

        return ($content > 0) ? true : false;
    }

    /**
     * Clear content
     *
     * @return void
     */
    public function clearContent()
    {
        $this->files['js'] = [];
        $this->files['css'] = [];
        $this->files['html'] = [];
    }

    /**
     * Add files
     *
     * @param string|array $files
     * @param string $fileType
     * @return bool
     */
    public function addFiles($files, $fileType)
    {
        if (\is_array($files) == false) {
            return false;
        }
        if (isset($this->files[$fileType]) == false) {
            $this->files[$fileType] = [];
        }
        foreach ($files as $file) {
            if (empty($file) == false) {
                \array_push($this->files[$fileType],$file);     
            }                  
        }

        return true;            
    }

    /**
     * Add component file
     *
     * @param string $fileExt
     * @return void
     */
    public function addComponentFile($fileExt)
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
     * @param string $file
     * @param string $fileType
     * @return void
     */
    public function addFile($file, $fileType)
    {
        if (\is_array($file) == false) {
            return false;
        }

        if (isset($this->files[$fileType]) == false) {
            $this->files[$fileType] = [];
        }
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
    protected function parseName($name)
    {
        if (\stripos($name,'::') !== false) {
            // extension component
            $tokens = \explode('::',$name);     
            $type = Self::EXTENSION_COMPONENT;
            $this->selectorType = '::';
        } elseif (stripos($name,'>') !== false) {
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
     * @return string|false
     */
    public function getPropertiesFileName() 
    {
        return (isset($this->files['properties']['file_name']) == true) ? $this->files['properties']['file_name'] : false;        
    }

    /**
     * Set properties file name
     *
     * @param string $fileName
     * @return void
     */
    public function setPropertiesFileName($fileName) 
    { 
        $this->files['properties']['file_name'] = $fileName;          
    }

    /**
     * Get options file name
     *
     * @return string|false
     */
    public function getOptionsFileName()
    {
        return (isset($this->files['options']['file_name']) == true) ? $this->files['options']['file_name'] : false;           
    }

    /**
     * Set options file name
     *
     * @param string $fileName
     * @return void
     */
    public function setOptionsFileName($fileName)
    {
        $this->files['options']['file_name'] = $fileName;
    }

    /**
     * Init component from array
     *
     * @param array $componentData
     * @return ComponentData
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
    public function toArray()
    {       
        return (array)$this;
    }

    /**
     * Get template url
     *
     * @return string|false
     */
    public function getTemplateUrl()
    {
        switch ($this->type) {
            case Self::TEMPLATE_COMPONENT:
                return Url::getTemplateUrl($this->templateName);
                
            case Self::EXTENSION_COMPONENT:
                return Url::getExtensionViewUrl($this->templateName);
               
            case Self::GLOBAL_COMPONENT:
                return Url::VIEW_URL;

            default: 
                return false;            
        }       
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->getTemplateUrl() . '/' . $this->basePath . '/' . $this->path . '/';
    }

    /**
     * Return root component name
     *
     * @return string
     */
    public function getRootComponentPath()
    {
        return Self::getTemplatePath($this->templateName,$this->type,$this->viewPath, $this->extensionsPath);
    }

    /**
     * Get template path
     *
     * @param string $template
     * @param string $type
     * @return string|false
     */
    public static function getTemplatePath($template, $type, $viewPath, $extensionsPath) 
    {   
        switch($type) {
            case Self::EXTENSION_COMPONENT:
                return $extensionsPath . $template . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

            case Self::TEMPLATE_COMPONENT:
                return $viewPath . 'templates' . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR;

            case Self::GLOBAL_COMPONENT:
                return $viewPath;
        }           
        
        return false;
    }

    public function getComponentPath()
    {
        return Self::getTemplatePath($this->templateName,$this->type,$this->viewPath, $this->extensionsPath);
    }

    /**
     * Get UI framework path
     *
     * @return string
     */
    public function getFrameworkPath()
    {
        return ((empty($this->framework) == false) && ($this->framework != $this->defaultFramework)) ? '.' . $this->framework . DIRECTORY_SEPARATOR : '';          
    }

    /**
     * Get component html file
     *
     * @param string $fileExt
     * @param string $language
     * @return string|false
     */
    public function getComponentFile($fileExt = 'html', $language = '') 
    {         
        $fileName = $this->getName() . $language . '.' . $fileExt;
        // try framework path
        if ($fileExt == 'json') {
            $fullFileName = $this->getFullPath() . $fileName;
        } else {
            $fullFileName = $this->getFullPath() . $this->frameworkPath . $fileName;   
            if (\file_exists($fullFileName) == true) {
                return $this->frameworkPath . $fileName;
            }     
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
    public function getFileUrl($fileName)
    {
        return $this->getUrl() . $fileName;
    }

    /**
     * Load properties json file
     *
     * @return array
     */
    public function loadProperties()
    {       
        $data = File::readJsonFile($this->getPropertiesFileName());  

        return (\is_array($data) == true) ? $data : [];                     
    }

    /**
     * Load options json file
     *
     * @return array
     */
    public function loadOptions()
    {         
        $data = File::readJsonFile($this->getOptionsFileName()); 

        return (\is_array($data) == true) ? $data : [];         
    }

    /**
     * Get component full path
     *
     * @param integer $type
     * @return string
     */
    public function getComponentFullPath($type, $templateName = null)
    {
        $templateName = (empty($templateName) == true) ? $this->templateName : $templateName;

        $templateFullPath = Self::getTemplatePath($templateName,$type,$this->viewPath,$this->extensionsPath); 
        $basePath = (empty($this->basePath) == false) ? $this->basePath : '';
        $path = $basePath . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR;   
        
        return $templateFullPath . $path;     
    }

    /**
     * Resolve component path
     *
     * @return void
     */
    protected function resolvePath() 
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
        $this->filePath = rtrim($templatePath,DIRECTORY_SEPARATOR) . $path;
    }

    /**
     * Resolve properties file name
     *
     * @return void
     */
    private function resolvePropertiesFileName()
    {
        $language = ($this->language != 'en') ? '-' . $this->language : '';
        $fileName = $this->getComponentFile('json',$language);

        if ($fileName === false) {
            $fileName = $this->getComponentFile('json');
            if ($fileName === false) {
                return false;
            }
        } 
        $this->setPropertiesFileName($this->getFullPath() . $fileName);   
    }

    /**
     * Resolve options file name
     *
     * @param string|null $path
     * @param integer     $iterations
     * @return bool
     */
    private function resolveOptionsFileName($path = null, $iterations = 0)
    {   
        $path = (empty($path) == true) ? $this->getFullPath() : $path;
    
        $fileName = $path . $this->optionsFile;
        if (\file_exists($fileName) == false) {
            $parentPath = Utils::getParentPath($path) . DIRECTORY_SEPARATOR;  
            if (empty($parentPath) == false && $iterations == 0) {
                return $this->resolveOptionsFileName($parentPath,1);
            }      
        }
    
        return $this->setOptionsFileName($fileName);
    }

    /**
     * Resolve component files
     *
     * @return void
     */
    private function resolveComponentFiles()
    {
        // js files
        $this->addComponentFile('js');
        // css file
        $this->addComponentFile('css');
        // html file
        $this->addComponentFile('html');        
    }
}
