<?php

namespace Pushkin;

use Hidehalo\Nanoid\Client;

class Reader extends BaseTranslator implements TranslatorContract {
    protected $source;

    public function translate()
    {
        $data = $this->findSource();

        $prefix = "<!-- pushkin-begin " . json_encode($data) . " -->";
        $postfix = "<!-- pushkin-end -->";

        return $prefix . $this->input . $postfix;
    }
}
