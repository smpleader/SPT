<?php
/**
 * SPT software - Plugin loader
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: A plugin supporter
 * @version: 0.8
 * 
 */

namespace SPT\Application\Plugin;

use SPT\Application\IApp;
use SPT\Log;
use SPT\Support\FncArray;
use \Exception;

class Manager
{
    protected array $list = [];
    protected string $master = '';
    protected string $message = '';
    protected array $calls = [];
    protected IApp $app;

    public function __construct(IApp $app, array $packages)
    {
        $this->app = $app;

        $filterActive = false;
        if(is_array($app->cf('activePlugins')))
        {
            $filterActive = $app->cf('activePlugins');
        }

        foreach($packages as $path=>$namespace)
        {
            $this->add($path, $namespace, $filterActive);
        }
    }

    protected function add(string $path, string $namespace, $filterActive)
    {
        foreach(new \DirectoryIterator($path) as $item) 
        {
            if (!$item->isDot() && $item->isDir())
            {
                $plg = $item->getBasename();
                if(is_array($filterActive) && !in_array($plg, $filterActive))
                {
                    continue;
                }

                $name = $namespace. $plg;
                $installer = $name. '\\registers\\Installer';
                $this->list[$plg] = class_exists($installer) ? $installer::info() : [];
                $this->list[$plg]['namespace'] =  $name;
                $this->list[$plg]['path'] =  $path. $plg. '/';
                $this->list[$plg]['name'] =  $plg;
            }
        }
    }

    public function call($sth, string $mode = 'single')
    {
        $this->calls = [];
        $this->message = '';

        switch($sth)
        {
            case 'all':
                $this->calls = $this->list;
                break;
            default:
                if(FncArray::isArrayString($sth))
                {
                    if($mode == 'tag')
                    {
                        //TODO: call plugin by tags
                        // sth == tag
                    }
                    else
                    {
                        foreach($sth as $plgName)
                        {
                            if(!$this->callPlugin($plgName))
                            {
                                Log::add('PluginManager: unvailable plugin '.$plgName); 
                            }
                        }
                    }
                }
                elseif(is_string($sth))
                {
                    if($mode == 'tag')
                    {
                        //TODO: call plugin by tags
                        // sth == tag
                    }
                    elseif(!$this->callPlugin($sth, $mode))
                    {
                        Log::add('PluginManager: unvailable plugin '. $sth. ' in mode '. $mode); 
                    }
                }
                else
                {
                    $this->message = 'PluginManager: invalid call ';
                }
                break;
        }

        return $this;
    }

    protected function hasTag(array $matchTags, string $pluginName)
    {
        // TODO: call plugin by tags 
        return false;
    }

    protected function callPlugin($pluginName, string $mode = 'single')
    {
        if(!isset($this->list[$pluginName]))
        {
            $this->message = 'Invalid plugin '. $pluginName;
            return false;
        }

        if($mode == 'single' || $mode == 'family')
        {
            $this->calls[$pluginName] = $this->list[$pluginName];
        }

        if($mode == 'children' || $mode == 'family')
        {
            $test = $pluginName. '_';
            foreach($this->list as $name => $plg)
            {
                if(strpos($name, $test) === 0)
                {
                    if(! $this->callPlugin($name) )
                    {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function run(string $event, string $function, bool $required = false, $closure = null, bool $outputResult=false)
    {
        if(empty($this->calls))
        {
            return;
        }

        if($this->message && $required)
        {
            throw new Exception($this->message);
        }
 
        $event = ucfirst(strtolower($event));
        $results = $outputResult ? [] : true;

        foreach($this->calls as $plugin => $pluginInfo)
        {
            $class = $pluginInfo['namespace']. '\\registers\\'. $event;
            if(!method_exists($class, $function))
            {
                if(!$required) continue;
                throw new Exception('Invalid plugin '. $plugin. ' with '. $event. '.'. $function);
            }

            $result = $class::$function($this->app);
            if(null !== $closure && is_callable($closure))
            {
                $ok = $closure( $result );
                if(false === $ok && $required)
                {
                    throw new Exception('Callback failed with plugin '. $plugin. ' when call '. $event .'.' . $function);
                }

                if( $outputResult )
                {
                    $results[$plugin] = ['result'=>$result, 'afterCallback' =>$ok];
                }
            }
            else
            {   
                if( $outputResult )
                {
                    $results[$plugin] = ['result'=>$result];
                }
            }
        }

        return $results;
    }

    public function getList()
    {
        return $this->list;
    }

    public function getDetail(string $name)
    {
        return isset($this->list[$name]) ? $this->list[$name] : false;
    }
}