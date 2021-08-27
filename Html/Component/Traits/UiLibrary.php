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

use Arikaim\Core\Packages\PackageManager;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Http\Url;
use Arikaim\Core\Utils\Text;

/**
 * UiLibrary helpers
 */
trait UiLibrary
{
    /**
     * Return library properties
     *
     * @param string $name
     * @param string|null $version
     * @return Collection
     */
    public function getLibraryProperties(string $name, ?string $version = null)
    {
        $properties = PackageManager::loadPackageProperties($name,Path::LIBRARY_PATH);
       
        if (empty($version) == true) {       
            return $properties;
        }
        $versions = $properties->get('versions',[]);
       

        $properties['files'] = (isset($versions[$version]) == true) ? $versions[$version]['files'] : [];
        if (isset($versions[$version]['async']) == true) {
            $properties['async'] = $versions[$version]['async'];
        }

        return $properties;
    }

    /**
     * Parse library name (name:version)
     *
     * @param string $libraryName
     * @return array
     */
    public function parseLibraryName(string $libraryName): array
    {
        $tokens = \explode(':',$libraryName);
        $version = $tokens[1] ?? null;
        $option = $tokens[2] ?? null;

        return [
            $tokens[0] ?? $libraryName,
            $version,
            (empty($option) == true && $version == 'async') ? 'async' : $option 
        ];
    }

    /**
     * Get library details
     *
     * @param string $libraryName
     * @return array
     */
    public function getLibraryDetails(string $libraryName): array
    {
        list($name,$version,$option) = $this->parseLibraryName($libraryName);
        $properties = $this->getLibraryProperties($name,$version); 
        $params = $this->resolveLibraryParams($properties);           
        $urlParams = ($properties->get('params-type') == 'url') ? '?' . \http_build_query($params) : '';                  
        $files = [];

        foreach($properties->get('files') as $file) {   
            $libraryFile = Path::getLibraryFilePath($libraryName,$file); 
            $fileType = \pathinfo($libraryFile,PATHINFO_EXTENSION);       
            $fileType = (empty($fileType) == true) ? 'js' : $fileType;
            $files[$fileType][] = [
                'url' => (Utils::isValidUrl($file) == true) ? $file . $urlParams : Url::getLibraryFileUrl($name,$file) . $urlParams
            ];               
        }  

        return [
            'files'       => $files,            
            'library'     => $libraryName,
            'async'       => $properties->get('async',($option == 'async')),
            'crossorigin' => $properties->get('crossorigin',null)
        ];
    }

    /**
     * Get library files
     *
     * @param string $libraryName
     * @param string|null $version
     * @param string|null $option
     * @return array
     */
    public function getLibraryFiles(string $libraryName, ?string $version, ?string $option = null): array
    {       
        $properties = $this->getLibraryProperties($libraryName,$version);          
        $params = $this->resolveLibraryParams($properties);  
        $paramsText = '';
        $urlParams = '';
        $files = [];

        if (count($params) > 0) {
            $urlParams = ($properties->get('params-type') == 'url') ? '?' . \http_build_query($params) : '';
            \array_walk($params,function (&$value,$key) {
                $value = ' ' . $key .'="'. $value. '"';
            });
            $paramsText = \implode(',',\array_values($params));
        }         
    
        $libraryPath = Path::getLibraryPath($libraryName);

        foreach($properties->get('files') as $file) {
            $type = \pathinfo($libraryPath . $file,PATHINFO_EXTENSION);
            $item = [
                'file'        => (Utils::isValidUrl($file) == true) ? $file . $urlParams : Url::getLibraryFileUrl($libraryName,$file) . $urlParams,
                'type'        => (empty($type) == true) ? 'js' : $type,
                'params'      => $params,
                'params_text' => $paramsText,
                'library'     => $libraryName,
                'async'       => $properties->get('async',($option == 'async')),
                'crossorigin' => $properties->get('crossorigin',null)
            ];          
            $files[] = $item;
        }   
        
        return $files;
    }

    /**
     * Resolve library params
     *
     * @param Collection $properties
     * @return array
     */
    public function resolveLibraryParams($properties)
    {      
        $params = $properties->get('params',[]);
        $vars = [
            'domian'   => DOMAIN,
            'base_url' => BASE_PATH
        ];

        $libraryParams = $this->libraryOptions[$properties['name']] ?? ['params' => []];
        $vars = \array_merge($vars,$libraryParams['params'] ?? []);
            
        return Text::renderMultiple($params,$vars);       
    }
}
