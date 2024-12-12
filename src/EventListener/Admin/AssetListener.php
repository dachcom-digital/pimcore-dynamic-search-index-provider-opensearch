<?php

namespace DsOpenSearchBundle\EventListener\Admin;

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BundleManagerEvents::JS_PATHS  => 'addJsFiles'
        ];
    }

    public function addJsFiles(PathsEvent $event): void
    {
        $event->addPaths([
            '/bundles/dsopensearch/js/backend/settings.js',
        ]);
    }

}
