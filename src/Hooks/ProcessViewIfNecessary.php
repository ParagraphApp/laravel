<?php

namespace Paragraph\Hooks;

use Illuminate\Contracts\View\View;
use Illuminate\View\Factory;
use Paragraph\Paragraph;
use Paragraph\ProxyTranslator;
use Paragraph\Reader;
use Paragraph\Storage\ViewStorage;
use Paragraph\Translator;
use Paragraph\TranslatorContract;

class ProcessViewIfNecessary {
    protected $views;

    public function __construct(ViewStorage $views)
    {
        $this->views = $views;
    }

    public function compose(View $view)
    {
        if (! Paragraph::isComposerEnabled() || ! $this->isABladeFile($view) || ! $this->isAParentView($view) || $this->views->has($view->name())) {
            return;
        }

        Paragraph::enableReader($view->name());
    }

    /**
     * @param View $view
     * @return bool
     */
    protected function isABladeFile(View $view)
    {
        return preg_match('/\.php$/', $view->getPath());
    }

    /**
     * @param View $view
     * @return bool
     */
    protected function isAParentView(View $view)
    {
        // A child view always has a render count higher than 1 - because we are inside the parent view
        // plus at least one more view - the child
        return $view->getFactory()->getRenderCount() == 1;
    }
}
