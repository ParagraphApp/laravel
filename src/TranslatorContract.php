<?php

namespace Pushkin;

interface TranslatorContract {
    /**
     * TranslatorContract constructor.
     * @param string $input
     */
    public function __construct($input);

    /**
     * @return string
     */
    public function translate();
}
