<?php

namespace Paragraph\Hooks;

use Illuminate\View\Factory;
use Illuminate\Contracts\View\Factory as FactoryContract;

class ViewFactoryWrapper extends Factory
{
    protected function viewInstance($view, $path, $data)
    {
        return new ViewWrapper($this, $this->getEngineFromPath($path), $view, $path, $data);
    }
}
