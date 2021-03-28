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

use Arikaim\Core\Utils\Utils;

/**
 * Include options for view components
 */
trait IncludeOption
{
    /**
     * Process component include js files option
     *      
     * @return void
     */
    protected function processIncludeOption()
    { 
        $include = $this->getOption('include');   
        $include = $include['js'] ?? null;
        if (empty($include) == true) {
            return;
        }

        $include = (\is_array($include) == false) ? [$include] : $include;   
        // include component files
        foreach ($include as $item) {             
            $file = $this->resolveIncludeFile($item);
            if (\is_null($file) == true) continue;

            $this->addFile($file,'js');
        }                 
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
        
        $component = $this->create($includeFile,'en');
        $component->init();
        $files = $component->getIncludeFile('js');
     
        if (empty($files) == true) {
            return null;
        }

        $this->addIncludedComponent($includeFile,$this->componentType);
        
        return [
            'url'            => $files,
            'component_name' => $includeFile,
            'type'           => $this->componentType
        ];                     
    }
}
