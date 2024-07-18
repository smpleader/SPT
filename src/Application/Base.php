<?php
/**
 * SPT software - Base application
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: An application integrate plugin engine
 * @version: 0.8
 * 
 */

namespace SPT\Application;
 
use SPT\Router\ArrayEndpoint as Router;
use SPT\Response;
use SPT\Container\IContainer;
use SPT\Application\Plugin\Manager;

class Base extends ACore implements IApp
{
    protected $plgManager;
    protected $packages;

    public function __construct(IContainer $container, string $publicPath, string $pluginPath, Configuration $config, string $namespace = '', array $packages = [])
    {
        if(!file_exists($publicPath) || !file_exists($pluginPath) )
        {
            die('System path not exists');
        }

        define('SPT_PUBLIC_PATH', $publicPath);
        define('SPT_PLUGIN_PATH', $pluginPath); 

        $this->namespace = empty($namespace) ? __NAMESPACE__ : $namespace;

        $this->packages = [SPT_PLUGIN_PATH => $this->namespace. '\\plugins\\'];
        if( count($packages) )
        {
            $this->packages = array_merge($this->packages, $packages);
        }

        $this->container = $container;

        $this->envLoad();
        
        $this->plgManager->call('all')->run('Bootstrap', 'initialize');

        return $this;
    }

    protected function envLoad()
    {
        $this->config = new Configuration(null);
        $this->plgManager = new Manager(
            $this,
            $this->packages
        );
    }

    public function execute(string | array $parameters = []){}

    public function redirect(string $url, $code = 302)
    {
        Response::redirect($url, $code );
        exit(0);
    }

    public function raiseError(string $msg, $code = 500)
    {
        Response::_($msg, $code);
        exit(0);
    }

    public function finalize($content)
    {
        Response::_200($content);
        exit(0);
    }

    /**
     * 
     *  SUPPORT PLUGIN ENGINE
     * 
     */

    public function plgLoad(string $event, string $function, $callback = null, bool $getResult = false)
    {
        return $this->plgManager->call('all')->run($event, $function, false, $callback, $getResult);
    }

    public function childLoad(string $event, string $function, $callback = null, bool $getResult = false)
    {
        $plugin = $this->get('mainPlugin', false);
        if(false === $plugin)
        {
            throw new \Exception('Method childLoad can not be called before Routing.'); 
        }

        return $this->plgManager->call($plugin['name'], 'children')->run($event, $function, false, $callback, $getResult);
    }

    public function familyLoad(string $event, string $function, $callback = null, bool $getResult = false)
    {
        $plugin = $this->get('mainPlugin', false);
        if(false === $plugin)
        {
            throw new \Exception('Method familyLoad can not be called before Routing.'); 
        }

        return $this->plgManager->call($plugin['name'], 'family')->run($event, $function, false, $callback, $getResult);
    }

    public function plugin($name = '')
    {
        return '' == $name ? $this->get('mainPlugin') : 
                ( true === $name ? 
                    $this->plgManager->getList() : 
                    $this->plgManager->getDetail($name) 
                );
    }

    /**
     * 
     *  SUPPORT MVVM ENGINE
     * 
     */

    protected array $vmClasses;
    public function getVMList(string $plgName)
    {
        return isset($this->vmClasses[$plgName]) ? $this->vmClasses[$plgName] : [];
    }

    public function addVM(string $plgName, string $name, string $fullName)
    {
        $this->vmClasses[$plgName][] = [$name, $fullName];
    }
}