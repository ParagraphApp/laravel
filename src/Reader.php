<?php

namespace Paragraph;

use Hidehalo\Nanoid\Client;

class Reader extends BaseTranslator implements TranslatorContract {
    protected $source;

    public function translate()
    {
        $data = $this->findSource();

        static::$texts[] = $data;

        $prefix = "<!-- pushkin-begin " . json_encode($data) . " -->";
        $postfix = "<!-- pushkin-end -->";

        return $prefix . $this->input . $postfix;
    }
}
