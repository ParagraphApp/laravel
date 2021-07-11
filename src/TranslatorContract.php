<?php

namespace Pushkin;

interface TranslatorContract {
    /**
     * TranslatorContract constructor.
     * @param string $input
     * @param int $startLine
     * @param int $endLine
     */
    public function __construct($input, $startLine, $endLine);

    /**
     * @return string
     */
    public function translate();
}
