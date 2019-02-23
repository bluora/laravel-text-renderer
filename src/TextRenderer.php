<?php

namespace HnhDigital\TextRenderer;

/*
 * This file is part of the Laravel Text Renderer package.
 *
 * (c) H&H|Digital <hello@hnh.digital>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use HnhDigital\TextRenderer\RendererPluginInterface;

/**
 * This is the Text Renderer class.
 *
 * @author Rocco Howard <rocco@hnh.digital>
 */
class TextRenderer
{
    /**
     * Config.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Placeholders.
     *
     * @var array
     */
    protected $placeholders = [];

    /**
     * Plugins.
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * Constructor.
     *
     * @return self
     */
    public function __construct()
    {
        foreach (config('hnhdigital.text-renderer.internal-plugins', []) as $name => $class) {
            $this->addPlugin($name, $class);
        }

        foreach (config('hnhdigital.text-renderer.plugins', []) as $name => $class) {
            $this->addPlugin($name, $class);
        }

        return $this;
    }

    /**
     * Set config.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return self
     */
    public function setConfig($key, $value)
    {
        Arr::set($this->config, $key, $value);

        return $this;
    }

    /**
     * Get config.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return self
     */
    public function getConfig($key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Has config.
     *
     * @param string $key
     *
     * @return self
     */
    public function hasConfig($key)
    {
        return Arr::has($this->config, $key);
    }

    /**
     * Set default placeholder.
     *
     * @param mixed  $data
     * @param string $other_key
     *
     * @return self
     */
    public function setDefaultPlaceholder($data, $other_key = null)
    {
        if (!is_null($other_key)) {
            $this->addPlaceholder($other_key, $data);
        }

        return $this->addPlaceholder('default', $data);
    }

    /**
     * Add placeholder.
     *
     * @param string $name
     * @param mxied $class
     *
     * @return self
     */
    public function addPlugin($name, $class)
    {
        if (is_string($plugin = $class)) {
            $plugin = new $class;
        }

        if (!($plugin instanceof RendererPluginInterface)) {
            return $this;
        }

        $plugin->connect($this);

        Arr::set($this->plugins, $name, $plugin);

        return $this;
    }

    /**
     * Add placeholder.
     *
     * @param string $key
     * @param mixed  $data
     *
     * @return self
     */
    public function addPlaceholder($key, $data)
    {
        Arr::set($this->placeholders, $key, $data);

        return $this;
    }

    /**
     * Add placeholders.
     *
     * @param array $placeholders
     *
     * @return self
     */
    public function addPlaceholders($placeholders)
    {
        foreach ($placeholders as $key => $data) {
            $this->addPlaceholder($key, $data);
        }

        return $this;
    }

    /**
     * Get placeholder.
     *
     * @return bool|array
     */
    public function getPlaceholder($key, $default = null)
    {
        return Arr::get($this->placeholders, $key, $default);
    }

    /**
     * Parse the text using the placeholders.
     *
     * @return self
     */
    public function parse($text)
    {
        $placeholders = [];

        // Model placeholders.
        preg_match_all('/\@([0-9a-zA-Z_-]+)(?:(?:\()(.*?)(?:\)))?(?:(?:{)(.*?)(?:}))?(?:(?:\[)(.*?)(?:\]))?/', $text, $matches);

        foreach ($matches[1] as $index => $match) {
            $empty_text = Arr::get($matches, '3.'.$index, '');
            $options = explode(',', Arr::get($matches, '4.'.$index, ''));

            if (empty($matches[2][$index])) {
                $placeholders[$matches[0][$index]] = [
                    'source'  => str_replace('-', '_', $matches[1][$index]),
                    'options' => $options,
                    'empty'   => $empty_text,
                ];

                continue;
            }

            $attribute_array = explode('|', $matches[2][$index]);
            $attribute_name = array_shift($attribute_array);

            $placeholders[$matches[0][$index]] = [
                'source'             => $matches[1][$index],
                'attribute'          => str_replace('-', '_', $attribute_name),
                'original_attribute' => $attribute_name,
                'settings'           => $attribute_array,
                'options'            => $options,
                'empty'              => $empty_text,
            ];
        }

        foreach ($placeholders as $original_value => $placeholder_data) {
            $source = Arr::get($placeholder_data, 'source');
            $attribute_name = Arr::get($placeholder_data, 'attribute', false);
            $original_attribute = Arr::get($placeholder_data, 'original_attribute', false);
            $settings = Arr::get($placeholder_data, 'settings', []);
            $options = Arr::get($placeholder_data, 'options', []);
            $empty_text = Arr::get($placeholder_data, 'empty', '');

            // Render default placeholders - @attribute-name[?]
            if ($attribute_name === false
                && Arr::has($this->placeholders, 'default')) {
                $replace_value = $this->getPlaceholderValue('default', $source, $settings, $empty_text);

            // Render using plugin.
            } elseif ($this->isPluginPlaceholder($source)) {
                $replace_value = $this->plugins[$source]->parse($original_attribute, $settings, $options, $empty_text);

            // Render from a placeholder that was provided.
            } else {
                $replace_value = $this->getPlaceholderValue($source, $attribute_name, $settings, $empty_text);
            }

            $replace_value = $this->parseValueOptions($replace_value, $options, $empty_text);

            $text = str_replace(
                $original_value,
                $replace_value,
                $text
            );
        }

        return $text;
    }

    /**
     * Check if the source placeholder is a plugin.
     *
     * @param string $source
     *
     * @return boolean
     */
    public function isPluginPlaceholder($source)
    {
        return Arr::has($this->plugins, $source);
    }

    /**
     * Check the source placeholder for the given attribute.
     *
     * @param string $source
     * @param string $name
     * @param array  $settings
     * @param string $empty_text
     *
     * @return string
     */
    public function getPlaceholderValue($source, $name, $settings = [], $empty_text = '')
    {
        // The placeholder has not been assigned.
        if (!Arr::has($this->placeholders, $source, false)
            || is_null($placeholder = Arr::get($this->placeholders, $source, false))) {
            return $empty_text;
        }

        if (is_array($placeholder)) {
            return Arr::get($placeholder, $name, $empty_text);
        }

        if (method_exists($placeholder, Str::camel($name))) {
            return $placeholder->{Str::camel($name)}($settings);
        }

        return data_get($placeholder, $name, $empty_text);
    }

    /**
     * Parse the given text with some options.
     *
     * @param string $value
     * @param array  $options
     *
     * @return string
     */
    public function parseValueOptions($value, $options, $empty_text)
    {
        if (empty($value) && !empty($empty_text)) {
            return $empty_text;
        }

        foreach ($options as $key) {
            switch ($key) {
                case 'lowercase':
                    $value = strtolower($value);
                    break;
                case 'uppercase':
                    $value = strtoupper($value);
                    break;
                case 'titlefirst':
                    $value = ucfirst($value);
                    break;
                case 'titlecase':
                    $value = ucwords($value);
                    break;
                case 'html':
                    $value = nl2br($value);
                    break;
            }
        }

        return $value;
    }
}
