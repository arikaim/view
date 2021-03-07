<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View;

use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;

use Arikaim\Core\Collection\Collection;
use Arikaim\Core\Interfaces\View\ViewInterface;
use Arikaim\Core\Interfaces\CacheInterface;

/**
 * View class
 */
class View implements ViewInterface
{
    /**
     * Cache save time
     *
     * @var integer
     */
    public static $cacheSaveTime = 4;

    /**
     * Template loader
     *
     * @var Twig\Loader\FilesystemLoader|null
     */
    private $loader = null;
    
    /**
     * Twig env
     *
     * @var \Twig\Environment|null
     */
    private $environment = null;

    /**
     * Cache
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * Vie wpath
     *
     * @var string
     */
    private $viewPath; 

    /**
     * Extensions Path
     *
     * @var string
     */
    private $extensionsPath;

    /**
     * Templates path
     *
     * @var string
     */
    private $templatesPath;

    /**
     * Components path
     *
     * @var string
     */
    private $componentsPath;

    /**
     * Page properties collection
     *
     * @var Collection
     */
    private $pageProperties;
    
    /**
     * Current extension class
     *
     * @var string|null
     */
    private $currentExtensionClass = null;

    /**
     * Twig view settigns
     *
     * @var array
     */
    private $settings = [];

    /**
     * Component include files (js)
     *
     * @var array
    */
    protected $componentFiles = [];

    /**
     * Included compoents
     *
     * @var array
     */
    protected $includedComponents = [];

    /**
     * Primary template
     *
     * @var string
     */
    protected $primaryTemplate;

    /**
     * Constructor
     *
     * @param CacheInterface $cache
     * @param string $viewPath
     * @param string $extensionsPath
     * @param string $templatesPath
     * @param string $componentsPath
     * @param array $settings
     * @param string|null $primaryTemplate
     */
    public function __construct(
        CacheInterface $cache,       
        string $viewPath,
        string $extensionsPath,
        string $templatesPath,
        string $componentsPath,
        array $settings = [],
        ?string $primaryTemplate = null)
    {
        $this->pageProperties = new Collection();
        $this->viewPath = $viewPath;      
        $this->extensionsPath = $extensionsPath;
        $this->templatesPath = $templatesPath;
        $this->componentsPath = $componentsPath;       
        $this->settings = $settings;      
        $this->cache = $cache;      
        $this->primaryTemplate = $primaryTemplate ?? 'system';

        Self::$cacheSaveTime = \defined('CACHE_SAVE_TIME') ? \constant('CACHE_SAVE_TIME') : Self::$cacheSaveTime;
    }

    /**
     * Add include file if not exists
     *
     * @param array $file
     * @param string $key
     * @param string $componentName
     * @param string $type
     * @return void
     */
    public function addIncludeFile(array $file, string $key, string $componentName, string $type = ''): void
    {
        $this->componentFiles[$key] = $this->componentFiles[$key] ?? [];

        $found = \in_array($file['url'],\array_column($this->componentFiles[$key],'url'));
        if ($found === false) {
            $file['component_name'] = (empty($file['source_component']) == true) ? $componentName : $file['source_component'];
            $file['component_type'] = (empty($type) == true) ? 'arikaim' : $type;
            $this->includedComponents[] = [
                'name' => $file['component_name'],
                'type' => $type
            ];
            $this->componentFiles[$key][] = $file;
        }      
    }

    /**
     * Get included components
     *
     * @return array
     */
    public function getIncludedComponents(): array
    {
        return $this->includedComponents;
    } 

    /**
     * Get components include files
     *
     * @return array
     */
    public function getComponentFiles(): array
    {
        $this->componentFiles['js'] = $this->componentFiles['js'] ?? [];
        $this->componentFiles['css'] = $this->componentFiles['css'] ?? [];
        
        return $this->componentFiles;
    }

    /**
     * Get extension funciton
     *
     * @param string|null $name
     * @return object|null
     */
    public function getFunction(?string $name = null)
    {
        $functions = $this->getEnvironment()->getFunctions();

        return $functions[$name] ?? null;
    }
    

    /**
     * Get UI library path
     *
     * @param string $libraryName
     * @return string
     */
    public function getLibraryPath(string $libraryName): string
    {
        return $this->viewPath . 'library' . DIRECTORY_SEPARATOR . $libraryName . DIRECTORY_SEPARATOR;
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
     * Set primary template
     *
     * @param string $templateName
     * @return void
     */
    public function setPrimaryTemplate(string $templateName): void
    {       
        $this->primaryTemplate = $templateName;
    }

    /**
     * Add global variable
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function addGlobal(string $name, $value): void
    {
        $this->getEnvironment()->addGlobal($name,$value);
    }

    /**
     * Get components path
     *
     * @return string
     */
    public function getComponentsPath(): string
    {
        return $this->componentsPath;
    }

    /**
     * Get templates path
     *
     * @return string
     */
    public function getTemplatesPath(): string
    {
        return $this->templatesPath;
    }

    /**
     * Get page properties
     *
     * @return Collection
     */
    public function properties()
    {
        return $this->pageProperties;
    }

    /**
     * Gte extensions path
     *
     * @return string
     */
    public function getExtensionsPath(): string
    {
        return $this->extensionsPath;
    }

    /**
     * Get view path
     *
     * @return string
     */
    public function getViewPath(): string
    {
        return $this->viewPath;
    }

    /**
     * Get cache
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Add template extension
     *
     * @param ExtensionInterface $extension
     * @return void
     */
    public function addExtension(ExtensionInterface $extension): void
    {
        $this->getEnvironment()->addExtension($extension);
        $this->currentExtensionClass = \get_class($extension);
    }

    /**
     * Render template
     *
     * @param string $template
     * @param array $params
     * @return string|null
     */
    public function fetch(string $template, array $params = []): ?string
    {       
        return $this->getEnvironment()->render($template,$params);
    }

    /**
     * Render template block
     *
     * @param string $template
     * @param string $block
     * @param array $params
     * @return string|null
     */
    public function fetchBlock(string $template, string $block, array $params = []): ?string
    {
        return $this->getEnvironment()->load($template)->renderBlock($block,$params);
    }

    /**
     * Render template from string
     *
     * @param string $string
     * @param array $params
     * @return string
     */
    public function fetchFromString(string $string, array $params = []): string
    {
        return $this->getEnvironment()->createTemplate($string)->render($params);
    }

    /**
     * Get twig extension
     *
     * @return ExtensionInterface
     */
    public function getExtension(string $class)
    {
        return $this->getEnvironment()->getExtension($class);
    }

    /**
     * Get current extension (last added)
     *
     * @return ExtensionInterface
     */
    public function getCurrentExtension()
    {
        return $this->getExtension($this->currentExtensionClass);
    }

    /**
     * Get Twig loader
     *
     * @return Twig\Loader\FilesystemLoader
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Get Twig environment
     *
     * @return \Twig\Environment
     */
    public function getEnvironment()
    {
        if (empty($this->environment) == true) {
            $this->resolveEnvironment();
        }

        return $this->environment;
    }

    /**
     * Create twig environment
     *
     * @param string|array|null $paths
     * @param array|null $settings
     * @return Environment
     */
    public function createEnvironment($paths = null, ?array $settings = null)
    {
        $loader = $this->createLoader($paths);
        $settings = $settings ?? $this->settings;

        $environment = new Environment($loader,$settings);
        
        return $environment;
    }

    /**
     * Create env instance
     *
     * @return void
     */
    protected function resolveEnvironment(): void
    {
        $this->environment = $this->createEnvironment();
        $demoMode = $this->settings['demo_mode'] ?? false;
       
        $this->environment->addGlobal('demo_mode',$demoMode);
        $this->environment->addGlobal('current_component_name',$demoMode);   
        $this->environment->addGlobal('current_language',null);      
        $this->environment->addGlobal('current_url_path',null);      
    }

    /**
     * Create template loader
     *   
     * @param string|array|null $paths
     * @return FilesystemLoader
     */
    private function createLoader($paths = null)
    {      
        $paths = (\is_string($paths) == true) ? [$paths] : $paths;
        if (empty($paths) == true) {
            $paths = [
                $this->extensionsPath,
                $this->templatesPath,
                $this->componentsPath
            ];           
        }
     
        return new FilesystemLoader($paths);
    }
}
