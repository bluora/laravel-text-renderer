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
}
