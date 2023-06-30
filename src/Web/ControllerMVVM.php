<?php
/**
 * SPT software - Controller
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: Just a core controller
 * 
 */

namespace SPT\Web;

use SPT\Application\IApp;
use SPT\Container\Client;   

class ControllerMVVM extends Controller
{
    protected $supportMVVM = true;

    public function toHtml()
    {
        $this->registerViewModels();
        return parent::toHtml();
    }

    public function toAjax()
    {
        $this->registerViewModels();
        return parent::toAjax();
    }

    public function registerViewModels()
    { 
        $this->getOverrideLayouts();

        // Load VMs for theme
        if( is_file(SPT_THEME_PATH.'/_viewmodels.php'))
        { 
            $vmlist = (array) require_once SPT_THEME_PATH.'/_viewmodels.php';
            foreach($vmlist as $line)
            {
                if(is_array($line))
                { 
                    @list($path, $namespace, $onlyWidget) = $line; 
                    if(null === $onlyWidget) $onlyWidget = true;
                    $this->loadVMFolder($path, $namespace, $onlyWidget);
                }
            }
        }
 
        $plgName = $this->app->get('currentPlugin');
        $pluginPath = $this->app->get('pluginPath');

        $this->loadVMFolder(
            $pluginPath. 'viewmodels/', 
            $this->app->get('namespace'). '\\viewmodels\\', 
        );
    }

    protected function loadVMFolder($path, $namespace, $onlyWidget = false)
    {
        if(!is_dir($path)) return;
 
        // TODO: brenchmark test with Loader::findClass
        /*Loader::findClass( 
            $pluginPath. '/viewmodels/', 
            $this->app->get('namespace'). '\models\\', 
            function($classname, $fullname) use (&$container)
            {
                $container->share( $classname, new $fullname($container), true);
            });*/

        foreach(new \DirectoryIterator($path) as $file) 
        {
            if(!$file->isDot() && $file->isDir())
            {
                $inner = $file->getBasename();
                $this->loadVMFolder($path. '/'.$inner, $namespace. '\\'. $inner, $onlyWidget);
            }
            elseif (!$file->isDot() && $file->isFile()) 
            {
                $filename = $file->getBasename(); 
                $vmName = substr($filename, 0, strlen($filename) - 4) ;
                $vmName = $namespace. $vmName;

                if(class_exists($vmName))
                {
                    $registers = $vmName::register();
                    if($onlyWidget)
                    {
                        foreach($registers as $i => $line)
                        {
                            if(strpos($line, 'widgets.') !== 0)
                            {
                                unset($registers[$i]);
                            }
                        }
                    } 

                    ViewModelHelper::prepareVM(
                        $vmName, 
                        $registers, 
                        $this->getContainer()
                    );
                }
            }
        }
    }
}