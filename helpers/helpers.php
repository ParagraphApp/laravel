<?php

if (! function_exists('p')) {
    /**
     * @param string $input
     * @return string
     */
    function p($input, $startLine = null, $endLine = null)
    {
        return resolve(\Pushkin\TranslatorContract::class, compact('input', 'startLine', 'endLine'))->translate();
    }
}
