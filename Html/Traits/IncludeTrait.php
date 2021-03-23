<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\View\Html\Traits;

use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;
use Arikaim\Core\Utils\Utils;

/**
 * Include options for view components
 */
trait IncludeTrait
{
    /**
     * Process component include js files option
     *
     * @param ComponentDescriptorInterface $component
     * @param string $fileType
     * @return ComponentDescriptorInterface
     */
    protected function processIncludeOption(ComponentDescriptorInterface $component)
    { 
        $include = $component->getOption('include');   
        $include = $include['js'] ?? null;
        if (empty($include) == true) {
            return $component;
        }

        $include = (\is_array($include) == false) ? [$include] : $include;   
        // include component files
        foreach ($include as $item) {     
            $file = $this->resolveIncludeFile($item);
            if (\is_null($file) == true) continue;

            $component->addFile($file,'js');
        }           

        return $component;
    }

    /**
     * Resolve include file
     *
     * @param string $includeFile  Component or Url     
     * @return array|null
     */
    protected function resolveIncludeFile(string $includeFile): ?array
    {
        if (Utils::isValidUrl($includeFile) == true) {             
            $tokens = \explode('|',$includeFile);
            $url = $tokens[0];
            $tokens[0] = 'external';
            $params = (isset($tokens[1]) == true) ? $tokens : [];  

            return [
                'url'              => $url,
                'params'           => $params,
                'source_component' => 'url'
            ];
        } 
        
        $descriptor = $this->createComponentDescriptor($includeFile,'en');
        $files = $descriptor->getIncludeFiles();
        if (empty($files['js']) == true) {
            return null;
        }
      
        return [
            'url'              => $files['js'],
            'params'           => null,
            'source_component' => $includeFile
        ];                     
    }

     /**
     * Return component files
     *
     * @param string $name  
     * @return array
     */
    public function getComponentFiles(string $name): array
    {        
        $files = $this->view->getCache()->fetch('component.files.' . $name);
        if ($files !== false) {
            return $files;
        }
        
        $descriptor = $this->createComponentDescriptor($name,'en');
        $files = $descriptor->getIncludeFiles();
    
        $this->view->getCache()->save('component.files.' . $name,$files);

        return $files;
    }
}
