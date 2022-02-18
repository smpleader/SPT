<?php
/**
 * SPT software - Route
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: A way to route the site based URL, todo: replace Router
 * 
 */

namespace SPT;

use SPT\Support\FncArray;

class Route extends BaseObj
{
    private $nodes;

    public function __construct(string $siteSubpath = '', string $protocol = '')
    {
        $p =  ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443 ) ) ? 'https' : 'http';

        if( empty($protocol) )
        {
            $protocol = $p;
        
        } else{
            
            // force protocol
            if($protocol != $p){
                header('Location: '.$protocol. '://'. $_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']);
                exit();
            }
        }

        $protocol .= '://';

        $current = $protocol. $_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI'];
        $this->set('current', $current);

        $more = parse_url( $current );
        foreach( $more as $key => $value)
        {
            $this->set( $key, $value);
        }

        $subPath = trim( $siteSubpath, '/');

        $actualPath = '/'; 
        
        $actualPath = empty($subPath) ? $more['path'] : substr($more['path'], strlen($subPath)+1);
        
        $subPath = empty($subPath) ? '/' : '/'. $subPath .'/';

        $this->set( 'root', $protocol. $_SERVER['HTTP_HOST']. $subPath );

        $this->set( 'actualPath', $actualPath);

        $this->set( 'isHome', ($actualPath == '/' || empty($actualPath)) );

    }

    public function import(array $sitemap = [])
    {
        if( count($sitemap) ) 
        {
            $arr = $this->get('sitemap', []);
            $arr = array_merge($arr, $this->flatNodes($sitemap));
            $this->set('sitemap', $arr);
        }
    }

    // support nested keys
    private function flatNodes($sitemap, $parentSlug='')
    {
        $arr = [];
        foreach($sitemap as $key => $inside)
        {
            if(empty($key)) $key = '/';
            elseif (strpos($key, '/') !== 0 && empty($parentSlug)) 
            {
                $key = '/'. $key;
            }

            if($key == '/' )
            {
                if( $parentSlug == '' )
                {
                    $this->set('home', $inside);
                }
                else
                {
                    $arr[$parentSlug. $key] = $inside;
                }
            }
            elseif(is_array($inside) && !isset($inside['fnc']))   //)
            {
                $arr = array_merge($arr, $this->flatNodes($inside, $key ));
            }
            else
            {
                if($parentSlug != '')
                {
                    $key = $parentSlug. '/'. $key;
                }

                $arr[$key] = $inside;
            }
        }
        return $arr;
    }
 
    public function url($asset = '')
    {
        return $this->get('root'). $asset;
    }

    public function pathFinding( $default = false, $callback = null)
    {
        $sitemap = $this->get('sitemap', []);
        $path = $this->get('actualPath');
        $isHome = $this->get('isHome');
        $this->set('sitenode', '');
        if(empty($default) && isset($sitemap[0]))
        {
            $default = $sitemap[0];
        }
        
        if($isHome){
            $found = $this->get('home', '');
            if( $found === '')
            {
                $found = $default;
            }
            else
            {
                $this->set('sitenode', '/');
            }
            return $found;
        }

        if( isset($sitemap[$path]) )
        {
            return $sitemap[$path];
        }
        
        $found = false;

        if( is_callable($callback))
        {
            $found = $callback($sitemap, $path);
        } 
        elseif(FncArray::isReady($sitemap)) 
        {
            foreach( $sitemap as $reg=>$value )
            {
                if (preg_match ('#'. $reg. '#i', $path, $matches))
                {
                    if( !is_array($value) || isset($value['fnc']))
                    {
                        $found = $value;
                        $this->set('sitenode', $reg);
                        break;
                    }
                }
            }
        }

        return ( $found === false ) ? $default : $found;
    }

    public function praseUrl(array $parameters)
    {
        $slugs = trim($this->get('actualPath', ''), '/');
        $sitenote = trim($this->get('sitenode', ''), '/');
        if( $slugs > $sitenote )
        {
            $slugs = trim(substr($slugs, strlen($sitenote)), '/');
            $values = explode('/', $slugs);
        }
        else
        {
            $values = [];
        }
        
        $vars = [];
        foreach($parameters as $index => $name)
        {
            $vars[$name] = isset($values[$index]) ? $values[$index] : null;
        }

        return $vars;
    }
}
