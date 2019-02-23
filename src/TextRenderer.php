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

/**
 * This is the Text Renderer class.
 *
 * @author Rocco Howard <rocco@hnh.digital>
 */

class TextRenderer
{
    /**
     * Placeholders.
     *
     * @var array
     */
    protected $placeholders = [];

    /**
     * Rregister placeholder source.
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
            $empty_text = Arr::get($matches, Arr::dot(3, $index));
            $options = explode(',', Arr::get($matches, Arr::dot(4, $index), ''));

            if (empty($matches[2][$index])) {
                $placeholders[$matches[0][$index]] = [
                    'name'    => str_replace('-', '_', $matches[1][$index]),
                    'options' => $options,
                    'empty'   => $empty_text,
                ];

                continue;
            }

            $placeholders[$matches[0][$index]] = [
                'name'      => $matches[1][$index],
                'attribute' => str_replace('-', '_', $matches[2][$index]),
                'options'   => $options,
                'empty'     => $empty_text,
            ];
        }

        foreach ($placeholders as $original_placeholder => $settings) {
            $name = Arr::get($settings, 'name');
            $attribute_name = Arr::get($settings, 'attribute', false);
            $options = Arr::get($settings, 'options', false);
            $empty_text = Arr::get($settings, 'empty', '');

            // Render user placeholders - @attribute-name[?]
            // @todo render non-user placeholder data assigned to message.
            if ($attribute_name === false) {1
                $text = str_replace(
                    $original_placeholder,
                    $this->renderOptions($this->to_user->{$name}, $options, $empty_text),
                    $text
                );

                continue;
            }

            // The placeholder has not been assigned.
            if (!array_has($this->placeholders, $name, false)
                || is_null($placeholder = Arr::get($this->placeholders, $name, false))) {
                $text = str_replace($original_placeholder, $empty_text, $text);

                continue;
            }

            if (is_array($placeholder)) {
                $replace_placeholder = Arr::get($placeholder, $attribute_name, '');
            } else {
                $replace_placeholder = data_get($placeholder, $attribute_name, '');
            }

            $replace_placeholder = $this->renderOptions($replace_placeholder, $options, $empty_text);

            $text = str_replace(
                $original_placeholder,
                $replace_placeholder,
                $text
            );
        }

        return $text;
    }

    /**
     * Apply options to this placeholder.
     *
     * @param string $value
     * @param array  $options
     *
     * @return string
     */
    private function renderOptions($value, $options, $empty_text)
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
