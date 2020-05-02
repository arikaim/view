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

use Arikaim\Core\Collection\Interfaces\CollectionInterface;
use Arikaim\Core\Collection\Collection;
use Arikaim\Core\Collection\Arrays;
use Arikaim\Core\Utils\Text;

/**
 * Page head class
 */
class PageHead extends Collection implements CollectionInterface, \Countable, \ArrayAccess, \IteratorAggregate
{
    /**
     * Property params
     *
     * @var array
     */
    protected $params;

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct($data = []) 
    {  
        parent::__construct($data);
        $this->data['og'] = [];
        $this->data['twitter'] = [];
        $this->params = [];
    }

    /**
     * Set property value param
     *
     * @param string $name
     * @param string $value
     * @return PageHead
     */
    public function param($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Add or set property
     *
     * @param string $name
     * @param array $arguments
     * @return PageHead
     */
    public function __call($name, $arguments)
    {       
        $value = trim($arguments[0]);
        $options = (isset($arguments[1]) == true) ? $arguments[1] : [];

        if (substr($name,0,2) == 'og') {
            $name = strtolower(substr($name,2));
            return $this->og($name,$value,$options);          
        }
        if (substr($name,0,7) == 'twitter') {
            $name = strtolower(substr($name,7));
            return $this->twitter($name,$value);
        }

        return $this->set($name,$value);      
    }

    /**
     * Set meta title, description and keywords
     *
     * @param array $data
     * @return PageHead
     */
    public function setMetaTags(array $data)
    {
        $this->set('title',$data['title']);
        $this->set('description',$data['description']);
        $this->set('keywords',$data['keywords']);
        
        return $this;
    }

    /**
     * Apply meta tags if values are empty
     *
     * @param array $data
     * @return PageHead
     */
    public function applyDefaultMetaTags(array $data)
    {
        $this->applyDefault('title',$data);
        $this->applyDefault('description',$data);
        $this->applyDefault('keywords',$data);    
        
        return $this;
    }

    /**
     * Set items value if not exist in collection
     *
     * @param array $items
     * @return PageHead
     */
    public function applyDefaultItems(array $items)
    {
        foreach ($items as $key => $value) {           
            if (empty($this->get($key)) == true) {               
                $this->set($key,$value);
            }
        }

        return $this;
    } 

    /**
     * Apply item value if is empty in collection
     *
     * @param string $keyName
     * @param array $data
     * @return PageHead
     */
    public function applyDefault($keyName, array $data)
    {
        if (empty($this->get($keyName)) == true) {
            $value = (isset($data[$keyName]) == true) ? $data[$keyName] : null;
            $this->set($keyName,$value);
        }

        return $this;
    }

    /**
     * Apply og property if value is not empty
     *
     * @param string $keyName
     * @return PageHead
     */
    public function applyOgProperty($keyName, $default = '')
    {
        $value = $this->get($keyName,$default);
        if (empty($value) == false) {
            $this->og($keyName,$value);
        }

        return $this;
    }

    /**
     * Apply twitter property if value is not empty
     *
     * @param string $keyName
     * @return PageHead
     */
    public function applyTwitterProperty($keyName, $default = '')
    {
        $value = $this->get($keyName,$default);
        if (empty($value) == false) {
            $this->twitter($keyName,$value);
        }

        return $this;
    }

    /**
     * Set keywords metatag
     *
     * @param string|array ...$keywords
     * @return PageHead
     */
    public function keywords(...$keywords)
    {
        $words = [];
        foreach ($keywords as $text) {          
            $text = Text::tokenize($text,' ',Text::LOWER_CASE,true);          
            $words = array_merge($words,$text);
        }
       
        return $this->set('keywords',Arrays::toString($words,','));     
    }

    /**
     * Set keywords if field is empty
     *
     * @param array ...$keywords
     * @return PageHead
     */
    public function applyDefaultKeywors(...$keywords)
    {
        if (empty($this->get('keywords')) == true) {
            $this->keywords(...$keywords);
        }

        return $this;
    }

    /**
     * Set Open Graph property
     *
     * @return PageHead
     */
    public function og($name, $value, $options = []) 
    {      
        return $this->addProperty('og',$name,$value,$options);
    }

    /**
     * Set Open Graph title property
     *
     * @param string|null $title
     * @return PageHead
     */
    public function ogTitle($title = null)
    {
        $title = $this->get('title',$title);   

        return $this->og('title',$title);
    }
    
    /**
     * Set Open Graph description property
     *
     * @param string|null $title
     * @return PageHead
     */
    public function ogDescription($description = null)
    {
        $description = $this->get('description',$description);   

        return $this->og('description',$description);
    }

    /**
     * Set twitter property
     *
     * @return void
     */
    public function twitter($name, $value, $options = [])
    {
        return $this->addProperty('twitter',$name,$value,$options);      
    }

    /**
     * Set twitter title property
     *
     * @param string|null $title
     * @return PageHead
     */
    public function twitterTitle($title = null)
    {
        $title = $this->get('title',$title); 

        return $this->twitter('title',$title);
    }

    /**
     * Set twitter description property
     *
     * @param string|null $description
     * @return PageHead
     */
    public function twitterDescription($description = null)
    {
        $description = $this->get('description',$description);     

        return $this->twitter('description',$description);
    }

    /**
     * Add property
     *
     * @param string $key
     * @param string $name
     * @param string $value
     * @param array $options
     * @return PageHead
     */
    protected function addProperty($key, $name, $value, $options = [])
    {
        $property = $this->createProperty($name,$value,$options);
        $this->data[$key][$name] = $property;
       // array_push($this->data[$key],$property);

        return $this;
    }

    /**
     * Create property array
     *
     * @param string] $name
     * @param string $value
     * @param array $options
     * @return array
     */
    protected function createProperty($name, $value, $options = [])
    {        
        return [            
            'name'      => strtolower($name),
            'value'     => Text::render($value,$this->getParams()),
            'options'   => $options
        ];
    }

    /**
     * Resolve properties
     *   
     * @param string $key
     * @return boolean
     */
    public function resolveProperties($key)
    {
        $items = (isset($this->data[$key]) == true) ? $this->data[$key] : false;
        if (is_array($items) == false) {
            return false;
        }

        $properties = [];
        foreach ($items as $name => $value) {
            $property = (is_array($value) == false) ? $this->createProperty($name,$value,[]) : $value;  
            array_push($properties,$property);            
        }
        $this->data[$key] = $properties;

        return true;
    }
}
