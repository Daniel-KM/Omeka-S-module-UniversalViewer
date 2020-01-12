<?php

/*
 * Copyright 2015-2019 Daniel Berthereau
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

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Module\Manager as ModuleManager;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(null, ['UniversalViewer\Controller\Player']);
    }

    protected function preInstall()
    {
        $js = __DIR__ . '/asset/vendor/uv/uv.js';
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

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
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
            \Omeka\Form\SettingForm::class,
            'form.add_input_filters',
            [$this, 'handleMainSettingsFilters']
        );
    }

    public function handleMainSettings(Event $event)
    {
        parent::handleMainSettings($event);

        $form = $event->getTarget();

        $translator = $this->getServiceLocator()->get('MvcTranslator');
        $message = $this->iiifServerIsActive()
            ? $translator->translate('The IIIF Server is active, so when no url is set, the viewer will use the standard routes.') // @translate;
            : $translator->translate('The IIIF Server is not active, so when no url is set, the viewer won’t be displayed. Furthermore, the viewer won’t display lists of items.'); // @translate

        /** @var \Omeka\Form\Element\PropertySelect $element */
        $element = $form->get('universalviewer')->get('universalviewer_manifest_property');
        $element->setOption('info', $translator->translate($element->getOption('info')) . ' ' . $message);
    }

    public function handleMainSettingsFilters(Event $event)
    {
        $event->getParam('inputFilter')
            ->get('universalviewer')
            ->add([
                'name' => 'universalviewer_manifest_property',
                'required' => false,
            ])
        ;
    }

    public function handleViewBrowseAfterItem(Event $event)
    {
        $view = $event->getTarget();
        $services = $this->getServiceLocator();
        // Note: there is no item-set show, but a special case for items browse.
        $isItemSetShow = (bool) $services->get('Application')
            ->getMvcEvent()->getRouteMatch()->getParam('item-set-id');
        if ($isItemSetShow) {
            echo $view->universalViewer($view->itemSet);
        } elseif ($this->iiifServerIsActive()) {
            echo $view->universalViewer($view->items);
        }
    }

    public function handleViewBrowseAfterItemSet(Event $event)
    {
        if (!$this->iiifServerIsActive()) {
            return;
        }

        $view = $event->getTarget();
        echo $view->universalViewer($view->itemSets);
    }

    public function handleViewShowAfterItem(Event $event)
    {
        $view = $event->getTarget();
        echo $view->universalViewer($view->item);
    }

    protected function iiifServerIsActive()
    {
        static $iiifServerIsActive;

        if (is_null($iiifServerIsActive)) {
            $module = $this->getServiceLocator()
                ->get('Omeka\ModuleManager')
                ->getModule('IiifServer');
            $iiifServerIsActive = $module && $module->getState() === ModuleManager::STATE_ACTIVE;
        }
        return $iiifServerIsActive;
    }
}
