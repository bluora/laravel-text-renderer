<?php

namespace HnhDigital\TextRenderer;

interface RendererPluginInterface
{
    /**
     * Generate signature.
     *
     * @param string $name      
     * @param array  $settings  
     * @param array  $options   
     * @param string $empty_text
     *
     * @return string
     */
    public function parse($name, $settings, $options, $empty_text);
}
