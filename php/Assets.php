<?php

namespace RussiaSanctions;

/**
 * A PHP wrapper to allow calling libraries to find the static assets in
 * this project
 */
class Assets
{
    /**
     * Directory where raw asset files are located
     *
     * @var string
     */
    const ASSETS_DIR = __DIR__ . '/../assets/';

    /**
     * Returns the html form of the blocked message
     *
     * @return string
     */
    public static function getHtml()
    {
        return file_get_contents(self::ASSETS_DIR . 'msg.html');
    }
}
