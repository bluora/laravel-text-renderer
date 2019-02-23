<?php

namespace HnhDigital\TextRenderer\Plugins;

use HnhDigital\TextRenderer\RendererPluginAbstract;

/**
 * This is the Signature plugin for the text renderer.
 *
 * @author Rocco Howard <rocco@publishinghouse.io>
 */
class DatePlugin extends RendererPluginAbstract
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
        $format = $name !== '' ? $name : 'd/m/Y';

        $timestamp = time();

        if (Arr::has($settings, 0)) {
            $timestamp += $settings[0];
        }

        return date($format, $timestamp);
    }
}
