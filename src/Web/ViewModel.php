<?php
/**
 * SPT software - ViewModel
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: Just a core view model, is a type of container
 * 
 */

namespace SPT\Web;

use SPT\Container\Client;
use SPT\Traits\ObjectHasInternalData;
use SPT\Support\ViewModel as VMHub;
use SPT\Support\LayoutId;
use SPT\Support\App;

class ViewModel extends Client
{
    use ObjectHasInternalData;

    /**
     * Get array of support layout
     */
    public function registerLayouts() {}

    /**
     * Convert array of settings into a hub
     * TODO: consider to call this function after currentPlugin initialized
     */
    protected function extractSettings($sth, array|string $token, string $vm = '')
    {
        if(empty($vm))
        {
            $tmp = new \ReflectionClass($this);
            $vm = $tmp->getShortName(). 'VM';
        }

        $currentPlugin = App::getInstance()->get('currentPlugin', '');
        if(empty($currentPlugin))
        {
            throw new \RuntimeException('You can not extract setting before intialize current plugin.');
        }
        
        $currentTheme = App::getInstance()->any('theme', 'theme.default', '');
        if(empty($currentTheme))
        {
            $currentTheme = $currentPlugin;
        }
        
        if(is_string($sth))
        {
            $id = LayoutId::implode($token, $sth, $currentPlugin, $currentTheme);
            VMHub::add($id, $vm, $sth);
        }
        elseif(is_array($sth))
        {
            //if (count($array) == count($array, COUNT_RECURSIVE))
            if(is_array($sth[array_key_first($sth)])) 
            {
                foreach($sth as $tmp)
                { 
                    $this->extractSettings( $tmp, $token, $vm);
                }
            }
            else
            {
                @list($layout, $fnc) = $sth;
                $id = LayoutId::implode($token, $layout, $currentPlugin, $currentTheme);
                VMHub::add( $id, $vm, $fnc);
            }
        }
    }

    /**
     * Get a state from a session
     * 
     * @param string   $key value name
     * @param mixed   $default default value if not set
     * @param string   $format value format filter
     * @param string   $request_type method type POST|GET|PUT|DELETE
     * @param string   $sessionName alias name in the session, in the case of field name is different to session name
     * 
     * @return mixed 
     */ 
    public function state(string $key, $default='', string $format='cmd', string $request_type='post', string $sessionName='')
    {
        if(empty($sessionName)) $sessionName = $key;

        $old = $this->session->get($sessionName, $default);
        $var = null;

        if( is_object( $this->request->{$request_type} ) )
        {
            $var = $this->request->{$request_type}->get($key, $old, $format);
            $this->session->set($sessionName, $var);
        }

        return $var;
    }
}