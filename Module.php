<?php

/*
 * Copyright 2015-2017 Daniel Berthereau
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

use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Module\Manager as ModuleManager;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;
use UniversalViewer\Form\Config as ConfigForm;

class Module extends AbstractModule
{
    protected $settings = [
        'universalviewer_manifest_property' => '',
        'universalviewer_append_item_set_show' => true,
        'universalviewer_append_item_show' => true,
        'universalviewer_append_item_set_browse' => false,
        'universalviewer_append_item_browse' => false,
        'universalviewer_class' => '',
        'universalviewer_style' => 'background-color: #000; height: 600px;',
        'universalviewer_locale' => 'en-GB:English (GB),fr:French',
    ];

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(null, 'UniversalViewer\Controller\Player');
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $settings = $serviceLocator->get('Omeka\Settings');
        $t = $serviceLocator->get('MvcTranslator');

        $js = __DIR__ . '/asset/vendor/uv/lib/embed.js';
        if (!file_exists($js)) {
            throw new ModuleCannotInstallException($t->translate('UniversalViewer library should be installed. See module’s installation documentation.')); // @translate
        }

        foreach ($this->settings as $name => $value) {
            $settings->set($name, $value);
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $settings = $serviceLocator->get('Omeka\Settings');

        foreach ($this->settings as $name => $value) {
            $settings->delete($name);
        }
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        if (version_compare($oldVersion, '3.4.1', '<')) {
            $settings = $serviceLocator->get('Omeka\Settings');

            $settings->set('universalviewer_manifest_description_property',
                $this->settings['universalviewer_manifest_description_property']);

            $settings->set('universalviewer_manifest_attribution_property',
                $this->settings['universalviewer_manifest_attribution_property']);

            $settings->set('universalviewer_manifest_attribution_default',
                $settings->get('universalviewer_attribution'));
            $settings->delete('universalviewer_attribution');

            $settings->set('universalviewer_manifest_license_property',
                $this->settings['universalviewer_manifest_license_property']);

            $settings->set('universalviewer_manifest_license_default',
                $settings->get('universalviewer_licence'));
            $settings->delete('universalviewer_licence');

            $settings->set('universalviewer_manifest_logo_default',
                $this->settings['universalviewer_manifest_logo_default']);

            $settings->set('universalviewer_append_item_set_show',
                $settings->get('universalviewer_append_collections_show'));
            $settings->delete('universalviewer_append_collections_show');

            $settings->set('universalviewer_append_item_show',
                $settings->get('universalviewer_append_items_show'));
            $settings->delete('universalviewer_append_items_show');

            $settings->set('universalviewer_append_item_set_browse',
                $this->settings['universalviewer_append_item_set_browse']);

            $settings->set('universalviewer_append_item_browse',
                $this->settings['universalviewer_append_item_browse']);

            $style = $this->settings['universalviewer_style'];
            $width = $settings->get('universalviewer_width') ?: '';
            if (!empty($width)) {
                $width = ' width:' . $width . ';';
            }
            $height = $settings->get('universalviewer_height') ?: '';
            if (!empty($height)) {
                $style = strtok($style, ';');
                $height = ' height:' . $height . ';';
            }
            $settings->set('universalviewer_style', $style . $width . $height);
            $settings->delete('universalviewer_width');
            $settings->delete('universalviewer_height');

            $settings->set('universalviewer_iiif_max_size',
                $settings->get('universalviewer_max_dynamic_size'));
            $settings->delete('universalviewer_max_dynamic_size');
        }

        if (version_compare($oldVersion, '3.5', '<=')
            && version_compare($newVersion, '3.5', '>=')
        ) {
            $settings = $serviceLocator->get('Omeka\Settings');

            $settings->set('universalviewer_manifest_property',
                $settings->get('universalviewer_alternative_manifest_property'));

            $oldSettings = [
                'universalviewer_manifest_description_property',
                'universalviewer_manifest_attribution_property',
                'universalviewer_manifest_attribution_default',
                'universalviewer_manifest_license_property',
                'universalviewer_manifest_license_default',
                'universalviewer_manifest_logo_default',
                'universalviewer_alternative_manifest_property',
                'universalviewer_iiif_creator',
                'universalviewer_iiif_max_size',
                'universalviewer_force_https',
                // Normally already removed.
                'universalviewer_attribution',
                'universalviewer_licence',
                'universalviewer_append_collections_show',
                'universalviewer_append_items_show',
                'universalviewer_width',
                'universalviewer_height',
                'universalviewer_max_dynamic_size',
            ];
            foreach ($oldSettings as $name) {
                $settings->delete($name);
            }
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');
        $moduleManager = $serviceLocator->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('IiifServer');

        // Note: there is no item-set show, but a special case for items browse.
        if ($module && $module->getState() == ModuleManager::STATE_ACTIVE
            && (
                $settings->get('universalviewer_append_item_set_show')
                || $settings->get('universalviewer_append_item_browse')
            )
        ) {
            $sharedEventManager->attach('Omeka\Controller\Site\Item',
                'view.browse.after', [$this, 'displayUniversalViewer']);
        }

        if ($settings->get('universalviewer_append_item_show')) {
            $sharedEventManager->attach('Omeka\Controller\Site\Item',
                'view.show.after', [$this, 'displayUniversalViewer']);
        }

        if ($settings->get('universalviewer_append_item_set_browse')) {
            $sharedEventManager->attach('Omeka\Controller\Site\ItemSet',
                'view.browse.after', [$this, 'displayUniversalViewer']);
        }
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $serviceLocator = $this->getServiceLocator();
        $formElementManager = $serviceLocator->get('FormElementManager');
        $form = $formElementManager->get(ConfigForm::class);

        // Allow to display fieldsets in config form.
        $vars = [];
        $vars['form'] = $form;
        return $renderer->render('universal-viewer/module/config.phtml', $vars);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');

        $params = $controller->getRequest()->getPost();
        // Manage fieldsets of params automatically (only used for the view).
        foreach ($params as $name => $value) {
            if (isset($this->settings[$name])) {
                $settings->set($name, $value);
            } elseif (is_array($value)) {
                foreach ($value as $subname => $subvalue) {
                    if (isset($this->settings[$subname])) {
                        $settings->set($subname, $subvalue);
                    }
                }
            }
        }
    }

    public function displayUniversalViewer(Event $event)
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');

        $moduleManager = $serviceLocator->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('IiifServer');

        $view = $event->getTarget();
        if ($settings->get('universalviewer_append_item_set_browse') && isset($view->itemSets)) {
            if ($module && $module->getState() == ModuleManager::STATE_ACTIVE) {
                echo $view->universalViewer($view->itemSets);
            }
        } elseif ($settings->get('universalviewer_append_item_set_show') && isset($view->itemSet)) {
            if ($module && $module->getState() == ModuleManager::STATE_ACTIVE) {
                echo $view->universalViewer($view->itemSet);
            }
        } elseif ($settings->get('universalviewer_append_item_browse') && isset($view->items)) {
            echo $view->universalViewer($view->items);
        } elseif ($settings->get('universalviewer_append_item_show') && isset($view->item)) {
            echo $view->universalViewer($view->item);
        }
    }
}
