<?php

namespace HnhDigital\TextRenderer\Plugins;

use HnhDigital\TextRenderer\RendererPluginAbstract;

/**
 * This is the Signature plugin for the text renderer.
 *
 * @author Rocco Howard <rocco@publishinghouse.io>
 */
class RoutePlugin extends RendererPluginAbstract
{
    /**
     * Return date.
     *
     * @param string $name      
     * @param array  $settings  
     * @param array  $options   
     * @param string $empty_text
     *
     * @return string
     */
    public function parse($name, $settings, $options, $empty_text)
    {
        $key_array = explode('|', $key);

        if (array_get($key_array, 0, false)) {
            // Default.
            $args = [];

            if (Arr::has($this->config, 'route.parameters')) {
                $args = Arr::get($this->config, 'route.parameters');
            }

            foreach ($settings as $source_name) {
                $source = Arr::get($this->placeholders, $source_name, null);

                if ($source instanceof \Illuminate\Database\Eloquent\Model) {
                    $args[$source_name] = $source->getKey();
                } elseif (is_string($source) && !empty($source)) {
                    $args[$source_name] = $source;
                } elseif (stripos($source_name, '=')) {
                    $source_name_array = explode('=', $source_name);
                    $args[$source_name_array[0]] = $source_name_array[1];
                }
            }

            try {
                $url = route($key, $args);

                return '<a href="'.$url.'" target="_blank">'.(empty($empty_text) ? 'here' : $empty_text).'</a>';
            } catch (\Exception $e) {
                return $e->getMessage();
            }            
        }

        return '[error - incorrect route]';
    }
}
