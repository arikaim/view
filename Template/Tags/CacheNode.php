<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View\Template\Tags;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Cache tag node class
 */
class CacheNode extends Node
{
    /**
     * Ext class name
     *
     * @var string
     */
    protected $twigExtensionClass;

    /**
     * Constructor
     *
     * @param mixed $key
     * @param mixed $saveTime
     * @param Node $body
     * @param integer $line
     * @param string $tag
     * @param string $twigExtensionClass
     */
    public function __construct($key, $saveTime, Node $body, int $line, string $tag, string $twigExtensionClass)
    {             
        $this->twigExtensionClass = $twigExtensionClass;

        parent::__construct(['body' => $body],[
            'key'      => $key,
            'saveTime' => $saveTime
        ],$line,$tag);
    }

    /**
     * Compile code
     *
     * @param Compiler $compiler
     * @return void
     */
    public function compile(Compiler $compiler): void
    {     
        $key = $this->getAttribute('key');
        $saveTime = $this->getAttribute('saveTime');
     
        $compiler
            ->addDebugInfo($this)          
            ->write('$cache = $this->env->getExtension("' . $this->twigExtensionClass . '")->getCache();')
            ->write('$cached = $cache->fetch("' . $key . '");')            
            ->write('if ($cached === false) { ')
                ->write("ob_start();\n")
                ->subcompile($this->getNode('body'))                          
                ->write('$cached = ob_get_clean();')             
                ->write('$cache->save("' . $key . '",' . '$cached);')
            ->write("}\n")                 
            ->write('echo $cached;');
    }
}
