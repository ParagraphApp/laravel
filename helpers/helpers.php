<?php

if (! function_exists('p')) {
    /**
     * @param string $input
     * @return string
     */
    function p($input)
    {
        return resolve(\Pushkin\TranslatorContract::class, compact('input'))->translate();
    }
}
