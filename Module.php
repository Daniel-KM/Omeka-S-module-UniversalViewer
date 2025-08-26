<?php declare(strict_types=1);

/*
 * Copyright 2015-2024 Daniel Berthereau
 * Copyright 2016-2017 BibLibre
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software. You can use, modify and/or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software’s author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user’s attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software’s suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace UniversalViewer;

if (!class_exists('Common\TraitModule', false)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;

class Module extends AbstractModule
{
    use TraitModule;

    const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(null, ['UniversalViewer\Controller\Player']);
    }

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $translate = $services->get('ControllerPluginManager')->get('translate');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.72')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.72'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }

        $js = __DIR__ . '/asset/vendor/uv/umd/UV.js';
        if (!file_exists($js)) {
            $services = $this->getServiceLocator();
            $t = $services->get('MvcTranslator');
            throw new ModuleCannotInstallException(
                sprintf(
                    $t->translate('The library "%s" should be installed.'), // @translate
                    'Universal Viewer'
                ) . ' '
                . $t->translate('See module’s installation documentation.')); // @translate
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.browse.after',
            [$this, 'handleViewBrowseAfterItem']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\ItemSet',
            'view.browse.after',
            [$this, 'handleViewBrowseAfterItemSet']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            [$this, 'handleViewShowAfterItem']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_elements',
            [$this, 'handleMainSettings']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
        );
    }

    public function handleViewBrowseAfterItem(Event $event): void
    {
        $view = $event->getTarget();
        $services = $this->getServiceLocator();
        
        // Check if viewer should be shown on browse pages
        $showOnBrowse = $view->siteSetting('universalviewer_show_browse', true);
        if (!$showOnBrowse) {
            return;
        }
        
        // Note: there is no item-set show, but a special case for items browse.
        $isItemSetShow = (bool) $services->get('Application')
            ->getMvcEvent()->getRouteMatch()->getParam('item-set-id');
        if ($isItemSetShow) {
            echo $view->universalViewer($view->itemSet);
        } elseif ($this->isModuleActive('IiifServer')) {
            echo $view->universalViewer($view->items);
        }
    }

    public function handleViewBrowseAfterItemSet(Event $event): void
    {
        if (!$this->isModuleActive('IiifServer')) {
            return;
        }

        $view = $event->getTarget();
        
        // Check if viewer should be shown on browse pages
        $showOnBrowse = $view->siteSetting('universalviewer_show_browse', true);
        if (!$showOnBrowse) {
            return;
        }
        
        echo $view->universalViewer($view->itemSets);
    }

    public function handleViewShowAfterItem(Event $event): void
    {
        // In Omeka S v4, if the player is set in the view, don't add it.
        $view = $event->getTarget();
        $services = $this->getServiceLocator();
        $currentTheme = $services->get('Omeka\Site\ThemeManager')->getCurrentTheme();
        $blockLayoutManager = $services->get('Omeka\ResourcePageBlockLayoutManager');
        $resourcePageBlocks = $blockLayoutManager->getResourcePageBlocks($currentTheme);
        foreach ($resourcePageBlocks['items'] ?? [] as $blocks) {
            if (in_array('universalViewer', $blocks)) {
                return;
            }
        }
        echo $view->universalViewer($view->item);
    }
}
