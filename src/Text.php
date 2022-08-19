<?php

namespace Paragraph;

use Illuminate\Contracts\Support\Htmlable;

class Text implements Htmlable {
    protected $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function toHtml()
    {
        return $this->text;
    }

    public function __toString()
    {
        return $this->text;
    }
}
