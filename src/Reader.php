<?php

namespace Paragraph;

use Hidehalo\Nanoid\Client;

class Reader extends BaseTranslator implements TranslatorContract {
    protected $source;

    public function translate()
    {
        $data = $this->findSource();

        static::$texts[] = $data;

        $prefix = "<!-- paragraph-begin " . json_encode($data) . " -->";
        $postfix = "<!-- paragraph-end -->";

        return $prefix . $this->input . $postfix;
    }
}
