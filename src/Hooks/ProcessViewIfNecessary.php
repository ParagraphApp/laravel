<?php

namespace Paragraph\Hooks;

use Illuminate\Contracts\View\View;
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
        if (! Paragraph::isComposerEnabled() || ! $this->isAParentView($view) || $this->views->has($view->name())) {
            return;
        }

        Paragraph::enableReader();

        $contents = $view->render();

        Paragraph::disableReader();

        $this->views->save($view->name(), $contents);
    }

    /**
     * @param View $view
     * @return bool
     */
    protected function isAParentView(View $view)
    {
        return ! isset($view->getData()['__env']);
    }
}
