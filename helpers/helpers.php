<?php

if (! function_exists('p')) {
    /**
     * @param string $input
     * @return string
     */
    function p($input)
    {
        return (new Pushkin\Reader($input))->translate();
    }
}