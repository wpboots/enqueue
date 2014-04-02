<?php

/**
 * Enqueue
 *
 * @package Boots
 * @subpackage Enqueue
 * @version 1.0.0
 * @license GPLv2
 *
 * Boots - The missing WordPress framework. http://wpboots.com
 *
 * Copyright (C) <2014>  <M. Kamal Khan>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

class Boots_Enqueue
{
    /**
     * Will hold the Boots instance.
     *
     * @var  Object
     */
    private $Boots;

    private $Settings;

    private $dir;
    private $url;

    private $Scripts = array();
    private $Styles = array();

    private $type = null;
    private $slug = null;

    public function __construct($Boots, $Settings, $dir, $url)
    {
        $this->Boots = $Boots;
        $this->Settings = $Settings;
        $this->dir = $dir;
        $this->url = $url;
    }

    protected function js($slug, $file, $Dependancies, $Params, $ver, $footer, $raw)
    {
        if(wp_script_is($slug))
        {
            return false;
        }

        if(!$file)
        {
            wp_enqueue_script($slug);
        }
        else
        {
            $file = $raw ? $file : ($this->Settings['APP_URL'] . '/' . $file);
            $ver = !$ver ? $this->Settings['APP_VERSION'] : $ver;
            wp_register_script($slug, $file, $Dependancies, $ver, $footer);
            wp_enqueue_script($slug);
            if(count($Params)) wp_localize_script($slug, str_replace('-', '_', $slug), $Params);
        }
    }

    protected function css($slug, $file, $Dependancies, $ver, $media, $raw)
    {
        if(wp_style_is($slug))
        {
            return false;
        }

        if(!$file)
        {
            wp_enqueue_style($slug);
        }
        else
        {
            $file = $raw ? $file : ($this->Settings['APP_URL'] . '/' . $file);
            $ver = !$ver ? $this->Settings['APP_VERSION'] : $ver;
            wp_register_style($slug, $file, $Dependancies, $ver, $media);
            wp_enqueue_style($slug);
        }
    }

    private function option($key, $val = false)
    {
        if(!$this->type || !$this->slug)
        {
            // notice of incorrect id.
            return false;
        }

        $type = $this->type;
        $slug = $this->slug;

        switch($type)
        {
            case 'script' :
                if($val === false)
                {
                    return $this->Scripts[$slug][$key];
                }
                $this->Scripts[$slug][$key] = $val;
            break;
            case 'style'  :
                if($val === false)
                {
                    return $this->Styles[$slug][$key];
                }
                $this->Styles[$slug][$key] = $val;
            break;
        }
    }

    public function requires($on)
    {
        $Deps = is_array($on) ? $on : array($on);
        $Dependancies = array_merge((array) $this->option('dependancies'), $Deps);
        $this->option('dependancies', $Dependancies);

        return $this;
    }

    public function vars($key, $val)
    {
        $Var = is_array($key) ? $key : array($key => $val);
        $Vars = array_merge((array) $this->option('vars'), $Var);
        $this->option('vars', $Vars);

        return $this;
    }

    public function version($v)
    {
        $this->option('version', $v);

        return $this;
    }

    public function source($file)
    {
        $this->option('src', $file);

        return $this;
    }

    public function script($slug, $raw = false)
    {
        $this->Scripts[$slug] = array(
            'src'          => false,
            'dependancies' => array(),
            'vars'         => array(),
            'version'      => $this->Settings['APP_VERSION'],
            'raw'          => $raw
        );

        $this->type = 'script';
        $this->slug = $slug;

        return $this;
    }

    public function raw_script($slug)
    {
        return $this->script($slug, true);
    }

    public function style($slug, $raw = false)
    {
        $this->Styles[$slug] = array(
            'src'          => false,
            'dependancies' => array(),
            'version'      => $this->Settings['APP_VERSION'],
            'raw'          => $raw
        );

        $this->type = 'style';
        $this->slug = $slug;
        return $this;
    }

    public function raw_style($slug)
    {
        return $this->style($slug, true);
    }

    public function done($footer_media = false)
    {
        if(!$this->type || !$this->slug)
        {
            // notice of incorrect id.
            return false;
        }
        $type = $this->type;
        $slug = $this->slug;

        if($type == 'script')
        {
            if(!isset($this->Scripts[$slug]))
            {
                // notice that script does not exist.
                // use Enqueue::script('slug', 'fileuri');
                return false;
            }

            $Script = $this->Scripts[$slug];

            $this->js(
                $slug,
                $Script['src'],
                $Script['dependancies'],
                $Script['vars'],
                $Script['version'],
                $footer_media,
                $Script['raw']
            );
        }
        else if($type == 'style')
        {
            if(!isset($this->Styles[$slug]))
            {
                // notice that style does not exist.
                // use Enqueue::style('slug', 'fileuri');
                return false;
            }

            $Style = $this->Styles[$slug];

            $footer_media = is_bool($footer_media)
            ? 'screen' : $footer_media;

            $this->css(
                $slug,
                $Style['src'],
                $Style['dependancies'],
                $Style['version'],
                $footer_media,
                $Style['raw']
            );
        }

        $this->type = null;
        $this->slug = null;
        return $this;
    }
}

