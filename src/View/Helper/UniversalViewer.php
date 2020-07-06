<?php

/*
 * Copyright 2015-2018 Daniel Berthereau
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

namespace UniversalViewer\View\Helper;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\Theme\Theme;
use Zend\View\Helper\AbstractHelper;

class UniversalViewer extends AbstractHelper
{
    /**
     * @var Theme The current theme, if any
     */
    protected $currentTheme;

    /**
     * Construct the helper.
     *
     * @param Theme|null $currentTheme
     */
    public function __construct(Theme $currentTheme = null)
    {
        $this->currentTheme = $currentTheme;
    }

    /**
     * Get the Universal Viewer for the provided resource.
     *
     * Proxies to {@link render()}.
     *
     * @param AbstractResourceEntityRepresentation|AbstractResourceEntityRepresentation[] $resource
     * @param array $options
     * @return string Html string corresponding to the viewer.
     */
    public function __invoke($resource, $options = [])
    {
        if (empty($resource)) {
            return '';
        }

        $view = $this->getView();

        // If the manifest is not provided in metadata, point to the manifest
        // created from Omeka files only when the Iiif Server is installed.
        $iiifServerIsActive = $view->getHelperPluginManager()->has('iiifUrl');

        // Prepare the url of the manifest for a dynamic collection.
        if (is_array($resource)) {
            if (!$iiifServerIsActive) {
                return '';
            }
            $urlManifest = $view->iiifUrl($resource);
            return $this->render($urlManifest, $options, 'multiple');
        }

        // Prepare the url for the manifest of a record after additional checks.
        $resourceName = $resource->resourceName();
        if (!in_array($resourceName, ['items', 'item_sets'])) {
            return '';
        }

        // Determine the url of the manifest from a field in the metadata.
        $urlManifest = '';
        $manifestProperty = $view->setting('universalviewer_manifest_property');
        if ($manifestProperty) {
            $urlManifest = $resource->value($manifestProperty);
            if ($urlManifest) {
                // Manage the case where the url is saved as an uri or a text.
                $urlManifest = $urlManifest->uri() ?: $urlManifest->value();
                return $this->render($urlManifest, $options, $resourceName);
            }
        }

        // If the manifest is not provided in metadata, point to the manifest
        // created from Omeka files if the module Iiif Server is enabled.
        if (!$iiifServerIsActive) {
            return '';
        }

        // Some specific checks.
        switch ($resourceName) {
            case 'items':
                // Currently, an item without files is unprocessable.
                if (count($resource->media()) == 0) {
                    // return $view->translate('This item has no files and is not displayable.');
                    return '';
                }
                break;
            case 'item_sets':
                if ($resource->itemCount() == 0) {
                    // return $view->translate('This collection has no item and is not displayable.');
                    return '';
                }
                break;
        }

        $urlManifest = $view->iiifUrl($resource);
        return $this->render($urlManifest, $options, $resourceName);
    }

    /**
     * Render a universal viewer for a url, according to options.
     *
     * @param string $urlManifest
     * @param array $options
     * @param string $resourceName
     * @return string Html code.
     */
    protected function render($urlManifest, array $options = [], $resourceName = null)
    {
        static $id = 0;

        $view = $this->view;

        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->prependStylesheet($assetUrl('css/universal-viewer.css', 'UniversalViewer'))
            ->prependStylesheet($assetUrl('vendor/uv/uv.css', 'UniversalViewer'));
        $view->headScript()
            ->appendFile($assetUrl('vendor/uv/lib/offline.js', 'UniversalViewer'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('vendor/uv/helpers.js', 'UniversalViewer'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('vendor/uv/uv.js', 'UniversalViewer'), 'text/javascript', ['defer' => 'defer']);

        $configUri = isset($options['config'])
            ? $this->basePath($options['config'])
            : $this->assetPath('universal-viewer/config.json', 'UniversalViewer');

        $config = [
            'id' => 'uv-' . ++$id,
            'root' => $assetUrl('vendor/uv/', 'UniversalViewer', false, false),
            'iiifResourceUri' => $urlManifest,
            'configUri' => $configUri,
            'embedded' => true,
        ];

        // $locale = $view->identity()
        //     ? $view->userSetting('locale')
        //     : ($view->params()->fromRoute('__SITE__')
        //         ? $view->siteSetting('locale')
        //         : ($view->setting('locale') ?: 'en-GB'));
        $config['locales'] = [
            ['name' => 'en-GB', 'label' => 'English'],
        ];

        $config += $options;

        return $view->partial('common/helper/universal-viewer', [
            'config' => $config,
        ]);
    }

    /**
     * Get an asset path for a site from theme or module (fallback).
     *
     * @param string $path
     * @param string $module
     * @return string|null
     */
    protected function assetPath($path, $module = null)
    {
        // Check the path in the theme.
        if ($this->currentTheme) {
            $filepath = OMEKA_PATH . '/themes/' . $this->currentTheme->getId() . '/asset/' . $path;
            if (file_exists($filepath)) {
                return $this->view->assetUrl($path, null, false, false);
            }
        }

        // As fallback, get the path in the module (the file must exist).
        if ($module) {
            $assetPath = $this->view->assetUrl($path, $module, false, false);
            return $assetPath;
        }
    }
}
