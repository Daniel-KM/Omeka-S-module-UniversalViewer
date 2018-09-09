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
use UniversalViewer\Form\ConfigForm;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Form\Fieldset;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;
use Zend\Form\Element;

class Module extends AbstractModule
{
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
        $js = __DIR__ . '/asset/vendor/uv/lib/embed.js';
        if (!file_exists($js)) {
            $t = $serviceLocator->get('MvcTranslator');
            throw new ModuleCannotInstallException(
                $t->translate('The UniversalViewer library should be installed.') // @translate
                    . ' ' . $t->translate('See module’s installation documentation.')); // @translate
        }

        $this->manageSettings($serviceLocator->get('Omeka\Settings'), 'install');
        $this->manageSiteSettings($serviceLocator, 'install');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $this->manageSettings($serviceLocator->get('Omeka\Settings'), 'uninstall');
        $this->manageSiteSettings($serviceLocator, 'uninstall');
    }

    protected function manageSettings($settings, $process, $key = 'config')
    {
        $config = require __DIR__ . '/config/module.config.php';
        $defaultSettings = $config[strtolower(__NAMESPACE__)][$key];
        foreach ($defaultSettings as $name => $value) {
            switch ($process) {
                case 'install':
                    $settings->set($name, $value);
                    break;
                case 'uninstall':
                    $settings->delete($name);
                    break;
            }
        }
    }

    protected function manageSiteSettings(ServiceLocatorInterface $serviceLocator, $process)
    {
        $siteSettings = $serviceLocator->get('Omeka\Settings\Site');
        $api = $serviceLocator->get('Omeka\ApiManager');
        $sites = $api->search('sites')->getContent();
        foreach ($sites as $site) {
            $siteSettings->setTargetId($site->id());
            $this->manageSettings($siteSettings, $process, 'site_settings');
        }
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        require_once 'data/scripts/upgrade.php';
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'addFormElementsSiteSettings']
        );

        // Note: there is no item-set show, but a special case for items browse.
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.browse.after',
            function (Event $event) {
                $view = $event->getTarget();
                $services = $this->getServiceLocator();
                $config = $services->get('Config');
                $siteSettings = $services->get('Omeka\Settings\Site');
                if ($siteSettings->get('universalviewer_append_item_set_show',
                    $config['universalviewer']['site_settings']['universalviewer_append_item_set_show'])
                ) {
                    echo $view->universalViewer($view->itemSet);
                } elseif ($this->iiifServerIsActive()
                    && $siteSettings->get('universalviewer_append_item_browse',
                        $config['universalviewer']['site_settings']['universalviewer_append_item_browse'])
                ) {
                    echo $view->universalViewer($view->items);
                }
            }
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Site\ItemSet',
            'view.browse.after',
            function (Event $event) {
                if ($this->iiifServerIsActive()) {
                    $view = $event->getTarget();
                    $services = $this->getServiceLocator();
                    $config = $services->get('Config');
                    $siteSettings = $services->get('Omeka\Settings\Site');
                    if ($siteSettings->get('universalviewer_append_item_set_browse',
                        $config['universalviewer']['site_settings']['universalviewer_append_item_set_browse'])
                    ) {
                        echo $view->universalViewer($view->itemSets);
                    }
                }
            }
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            function (Event $event) {
                $view = $event->getTarget();
                $services = $this->getServiceLocator();
                $config = $services->get('Config');
                $siteSettings = $services->get('Omeka\Settings\Site');
                if ($siteSettings->get('universalviewer_append_item_show',
                    $config['universalviewer']['site_settings']['universalviewer_append_item_show'])
                ) {
                    echo $view->universalViewer($view->item);
                }
            }
        );
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(ConfigForm::class);

        $data = [];
        $defaultSettings = $config[strtolower(__NAMESPACE__)]['config'];
        foreach ($defaultSettings as $name => $value) {
            $data[$name] = $settings->get($name);
        }

        $form->init();
        $form->setData($data);

        return $renderer->render('universal-viewer/module/config', [
            'form' => $form,
        ]);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');

        $params = $controller->getRequest()->getPost();

        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $form->init();
        $form->setData($params);
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        $defaultSettings = $config[strtolower(__NAMESPACE__)]['config'];
        foreach ($params as $name => $value) {
            if (array_key_exists($name, $defaultSettings)) {
                $settings->set($name, $value);
            }
        }
    }

    public function addFormElementsSiteSettings(Event $event)
    {
        $services = $this->getServiceLocator();
        $siteSettings = $services->get('Omeka\Settings\Site');
        $config = $services->get('Config');
        $form = $event->getTarget();

        $defaultSiteSettings = $config[strtolower(__NAMESPACE__)]['site_settings'];

        // The module iiif server is required to display collections of items.
        $iiifServerIsActive = $this->iiifServerIsActive();

        $fieldset = new Fieldset('universal_viewer');
        $fieldset->setLabel('UniversalViewer');

        $fieldset->add([
            'name' => 'universalviewer_append_item_set_show',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item set page', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'universalviewer_append_item_set_show',
                    $defaultSiteSettings['universalviewer_append_item_set_show']
                ),
            ],
        ]);

        $fieldset->add([
            'name' => 'universalviewer_append_item_show',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item page', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'universalviewer_append_item_show',
                    $defaultSiteSettings['universalviewer_append_item_show']
                ),
            ],
        ]);

        $fieldset->add([
            'name' => 'universalviewer_append_item_set_browse',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item sets browse page', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'universalviewer_append_item_set_browse',
                    $defaultSiteSettings['universalviewer_append_item_set_browse']
                ),
                'disabled', !$iiifServerIsActive,
            ],
        ]);

        $fieldset->add([
            'name' => 'universalviewer_append_item_browse',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item browse page', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'universalviewer_append_item_browse',
                    $defaultSiteSettings['universalviewer_append_item_browse']
                ),
                'disabled', !$iiifServerIsActive,
            ],
        ]);

        $fieldset->add([
            'name' => 'universalviewer_class',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Class of main div', // @translate
                'info' => 'Class to add to the main div.',  // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'universalviewer_class',
                    $defaultSiteSettings['universalviewer_class']
                ),
            ],
        ]);

        $fieldset->add([
            'name' => 'universalviewer_style',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Inline style', // @translate
                'info' => 'If any, this style will be added to the main div of the Universal Viewer.' // @translate
                . ' ' . 'The height may be required.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'universalviewer_style',
                    $defaultSiteSettings['universalviewer_style']
                ),
            ],
        ]);

        $fieldset->add([
            'name' => 'universalviewer_locale',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Locales of the viewer', // @translate
                'info' => 'Currently not working', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'universalviewer_locale',
                    $defaultSiteSettings['universalviewer_locale']
                ),
            ],
        ]);

        $form->add($fieldset);
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
