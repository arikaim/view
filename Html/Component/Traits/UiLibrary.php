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
            $properties['files'] = $properties->get('files',[]);
            return $properties;
        }
        $versions = $properties->get('versions',[]);
        $properties['files'] = (isset($versions[$version]) == true) ? $versions[$version]['files'] : [];

        return $properties;
    }

    /**
     * Parse library name   (name:version)
     *
     * @param string $libraryName
     * @return array
     */
    public function parseLibraryName(string $libraryName): array
    {
        $nameTokens = \explode(':',$libraryName);
        $libraryName = $nameTokens[0] ?? $libraryName;
        $libraryVersion = $nameTokens[1] ?? null;
        $libraryOption = $nameTokens[2] ?? $libraryVersion;
        $include = ($libraryOption == 'include');

        return [$libraryName,$libraryVersion,$include];
    }

    /**
     * Get library details
     *
     * @param string $libraryName
     * @return array
     */
    public function getLibraryDetails(string $libraryName): array
    {
        list($name, $version) = $this->parseLibraryName($libraryName);
        $properties = $this->getLibraryProperties($name,$version);                   
        $files = [];

        foreach($properties->get('files') as $file) {   
            $libraryFile = Path::getLibraryFilePath($libraryName,$file); 
            $fileType = \pathinfo($libraryFile,PATHINFO_EXTENSION);       
            $files[$fileType][] = [
                'url' => (Utils::isValidUrl($file) == true) ? $file : Url::getLibraryFileUrl($name,$file) 
            ];               
        }  

        return [
            'files'       => $files,            
            'library'     => $libraryName,
            'async'       => $properties->get('async',false),
            'crossorigin' => $properties->get('crossorigin',null)
        ];
    }

    /**
     * Get library files
     *
     * @param string $name
     * @return array
     */
    public function getLibraryFiles(string $name): array
    {
        list($libraryName,$libraryVersion,$forceInclude) = $this->parseLibraryName($name);
        $properties = $this->getLibraryProperties($libraryName,$libraryVersion);   
        if ($properties->get('disabled',false) === true) {              
            return [];
        }
      
        $params = $this->resolveLibraryParams($properties);           
        $urlParams = ($properties->get('params-type') == 'url') ? '?' . \http_build_query($params) : '';
        $files = [];

        foreach($properties->get('files') as $file) {
            $libraryFile = Path::getLibraryFilePath($libraryName,$file);
            $type = \pathinfo($libraryFile,PATHINFO_EXTENSION);
            $item = [
                'file'        => (Utils::isValidUrl($file) == true) ? $file . $urlParams : Url::getLibraryFileUrl($libraryName,$file) . $urlParams,
                'type'        => (empty($type) == true) ? 'js' : $type,
                'params'      => $params,
                'library'     => $libraryName,
                'async'       => $properties->get('async',false),
                'crossorigin' => $properties->get('crossorigin',null)
            ];          
            $files[] = $item;
        }   
        
        return $files;
    }
}
