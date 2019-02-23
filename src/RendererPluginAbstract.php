<?php

namespace HnhDigital\TextRenderer;

/**
 * This is the Signature plugin for the text renderer.
 *
 * @author Rocco Howard <rocco@publishinghouse.io>
 */
abstract class RendererPluginAbstract implements RendererPluginInterface
{
    /**
     * @var HnhDigital\TextRenderer\TextRenderer
     */
    protected $renderer;

    /**
     * Connect renderer to plugin.
     *
     * @return self
     */
    public function connect(&$renderer)
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Has config key.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function hasConfig($key)
    {
        return $this->renderer->hasConfig($key);
    }

    /**
     * Get config key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getConfig($key)
    {
        return $this->renderer->getConfig($key);
    }

    /**
     * Get placeholder.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getPlaceholder($key)
    {
        return $this->renderer->getPlaceholder($key);
    }
}
