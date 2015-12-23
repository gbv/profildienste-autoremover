<?php

namespace Util;

/**
 * Class Util
 * @package Util
 *
 * Utility class for misc. functions
 */
class Util {

    /**
     * Checks if a given path contains a trailing slash and appends one if it doesn't
     *
     * @param $path
     * @return string
     */
    public static function addTrailingSlash($path){
        return rtrim($path, '/').'/';
    }
}