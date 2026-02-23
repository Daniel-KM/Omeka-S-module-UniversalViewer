<?php declare(strict_types=1);

/*
 * Copyright 2015-2025 Daniel Berthereau
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

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\Theme\Theme;

class UniversalViewer extends AbstractHelper
{
    /**
     * @var \Omeka\Site\Theme\Theme
     *
     * The current theme, if any.
     */
    protected $currentTheme;

    /**
     * @var bool
     */
    protected $isSite;

    /**
     * @var string
     *
     * "2", "3" or "4" (version of UniversalViewer).
     */
    protected $version;

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
    public function __invoke($resource, $options = []): string
    {
        if (empty($resource)) {
            return '';
        }

        $view = $this->getView();
        $this->isSite = $view->status()->isSiteRequest();
        $this->version = $this->isSite
            ? (string) $this->view->siteSetting('universalviewer_version', '4')
            : (string) $this->view->setting('universalviewer_version', '4');

        // If the manifest is not provided in metadata, point to the manifest
        // created from Omeka files only when the Iiif Server is installed.
        $iiifServerIsActive = $view->getHelperPluginManager()->has('iiifUrl');

        $isCollection = is_array($resource);

        // Prepare the url of the manifest for a dynamic collection.
        if ($isCollection) {
            if (!$iiifServerIsActive) {
                return '';
            }
            // Convert media to their parent items for the collection, since
            // the iiifserver/set route only handles items and item sets.
            $collectionResources = [];
            $seen = [];
            foreach ($resource as $res) {
                if ($res->resourceName() === 'media') {
                    $res = $res->item();
                }
                $id = $res->id();
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    $collectionResources[] = $res;
                }
            }
            if (!$collectionResources) {
                return '';
            }
            if (count($collectionResources) === 1) {
                $resource = reset($collectionResources);
            } else {
                $urlManifest = $view->iiifUrl($collectionResources);
                return $this->render($urlManifest, $options, 'multiple');
            }
        }

        // Prepare the url for the manifest of a record after additional checks.
        $resourceName = $resource->resourceName();

        // For a media, use the parent item manifest.
        if ($resourceName === 'media') {
            $resource = $resource->item();
            $resourceName = 'items';
        }

        if (!in_array($resourceName, ['items', 'item_sets'])) {
            return '';
        }

        // Determine the url of the manifest from a field in the metadata.
        $externalManifest = $view->iiifManifestExternal($resource, $iiifServerIsActive);
        if ($externalManifest) {
            return $this->render($externalManifest, $options, $resourceName);
        }

        // If the manifest is not provided in metadata, point to the manifest
        // created from Omeka files if the module Iiif Server is enabled.
        if (!$iiifServerIsActive) {
            return '';
        }

        // Some specific checks.
        switch ($resourceName) {
            case 'items':
                /** @var \Omeka\Api\Representation\ItemRepresentation $resource */
                // Currently, an item without files is unprocessable.
                $medias = $resource->media();
                if (!count($medias)) {
                    // return $view->translate('This item has no files and is not displayable.');
                    return '';
                }
                break;
            case 'item_sets':
                /** @var \Omeka\Api\Representation\ItemSetRepresentation $resource */
                if (!$resource->itemCount()) {
                    // return $view->translate('This collection has no item and is not displayable.');
                    return '';
                }
                break;
        }

        $urlManifest = $view->iiifUrl($resource);
        return $this->render($urlManifest, $options, $resourceName);
    }

    /**
     * Render a universal viewer v2, v3 or v4 for a url, according to options.
     *
     * @param string $urlManifest
     * @param array $options
     * @param string $resourceName
     * @return string Html code.
     */
    protected function render($urlManifest, array $options = [], $resourceName = null)
    {
        if ($this->version === '2') {
            return $this->renderUv2($urlManifest, $options, $resourceName);
        } elseif ($this->version === '3') {
            return $this->renderUv3($urlManifest, $options, $resourceName);
        } else {
            return $this->renderUv4($urlManifest, $options, $resourceName);
        }
    }

    protected function renderUv2($urlManifest, array $options = [], $resourceName = null)
    {
        static $id = 0;

        $plugins = $this->view->getHelperPluginManager();
        $assetUrl = $plugins->get('assetUrl');
        $setting = $plugins->get('setting');
        $siteSetting = $plugins->get('siteSetting');
        $mainOrSiteSetting = $this->isSite ? $siteSetting : $setting;

        $this->view->headLink()
            ->prependStylesheet($assetUrl('css/universal-viewer.css', 'UniversalViewer'));
        $this->view->headScript()
            ->appendFile(
                    $assetUrl('vendor/uv2/lib/embed.js', 'UniversalViewer', false, false),
                    'application/javascript',
                    ['id' => 'embedUV']
                )
                ->appendScript('/* wordpress fix */', 'application/javascript');

        $configUri = isset($options['config'])
            ? $this->view->basePath($options['config'])
            : $this->assetPath('universal-viewer/config.json', 'UniversalViewer');

        $config = [
            'id' => 'uv-' . ++$id,
            'root' => $assetUrl("vendor/uv2/", 'UniversalViewer', false, false),
            'iiifResourceUri' => $urlManifest,
            'configUri' => $configUri,
            'embedded' => true,
            'style' => 'background-color: #000; height: 600px;'
        ];

        $locale = $this->view->identity()
            ? (string) $this->view->userSetting('locale')
            : (string) $mainOrSiteSetting('locale');
        if (mb_strlen($locale) === 2) {
            $locale = mb_strtolower($locale) . '-' . mb_strtoupper($locale);
        }
        $config['locale'] = in_array($locale, [
                'cy-GB',
                'en-GB',
                'fr-FR',
            ])
            ? $locale
            : 'en-GB';

        $config += $options;

        return $this->view->partial('common/universal-viewer', [
            'config' => $config,
            'version' => '2',
        ]);
    }

    protected function renderUv3($urlManifest, array $options = [], $resourceName = null)
    {
        static $id = 0;

        $plugins = $this->view->getHelperPluginManager();
        $assetUrl = $plugins->get('assetUrl');
        /*
        $setting = $plugins->get('setting');
        $siteSetting = $plugins->get('siteSetting');
        $mainOrSiteSetting = $this->isSite ? $siteSetting : $setting;
        */

        $this->view->headLink()
            ->prependStylesheet($assetUrl('css/universal-viewer.css', 'UniversalViewer'))
            ->prependStylesheet($assetUrl("vendor/uv3/uv.css", 'UniversalViewer'));
        $this->view->headScript()
            ->appendFile($assetUrl("vendor/uv3/lib/offline.js", 'UniversalViewer'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl("vendor/uv3/helpers.js", 'UniversalViewer'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl("vendor/uv3/uv.js", 'UniversalViewer'), 'text/javascript', ['defer' => 'defer']);

        $configUri = isset($options['config'])
            ? $this->view->basePath($options['config'])
            : $this->assetPath('universal-viewer/config.json', 'UniversalViewer');

        $config = [
            'id' => 'uv-' . ++$id,
            'root' => $assetUrl('vendor/uv3/', 'UniversalViewer', false, false),
            'iiifResourceUri' => $urlManifest,
            'configUri' => $configUri,
            'embedded' => true,
        ];

        $config += $options;

        return $this->view->partial('common/universal-viewer', [
            'config' => $config,
            'version' => '3',
        ]);
    }

    protected function renderUv4($urlManifest, array $options = [], $resourceName = null)
    {
        static $id = 0;

        $plugins = $this->view->getHelperPluginManager();
        $assetUrl = $plugins->get('assetUrl');
        $setting = $plugins->get('setting');
        $siteSetting = $plugins->get('siteSetting');
        $mainOrSiteSetting = $this->isSite ? $siteSetting : $setting;

        $this->view->headLink()
            ->prependStylesheet($assetUrl('css/universal-viewer.css', 'UniversalViewer'))
            ->prependStylesheet($assetUrl("vendor/uv/uv.css", 'UniversalViewer'));
        $this->view->headScript()
           ->appendFile($assetUrl("vendor/uv/umd/UV.js", 'UniversalViewer'), 'text/javascript', ['defer' => 'defer']);

        $themeConfig = (bool) $mainOrSiteSetting('universalviewer_config_theme', false);
        if ($themeConfig) {
            // Deprecated.
            $configUri = isset($options['config'])
                ? $this->view->basePath($options['config'])
                : $this->assetPath('universal-viewer/uv-config.json', 'UniversalViewer');
            $config = [
                'id' => 'uv-' . ++$id,
                'root' => $assetUrl('vendor/uv/', 'UniversalViewer', false, false),
                'manifest' => $urlManifest,
                'configUri' => $configUri,
                'embedded' => false,
            ];
        } else {
            $mainConfig = $setting('universalviewer_config', '{}');
            $mainConfig = json_decode($mainConfig, true) ?: [];
            $siteConfig = $siteSetting('universalviewer_config', '{}');
            $siteConfig = json_decode($siteConfig, true) ?: [];
            $config = [
                'id' => 'uv-' . ++$id,
                'root' => $assetUrl('vendor/uv/', 'UniversalViewer', false, false),
                'manifest' => $urlManifest,
                'embedded' => false,
            ];
            $config = array_merge($config, $siteConfig, $mainConfig);
        }

       // For internal locales, only the name is needed.
       // The first is the default locale. The others are optional.
       // Default locales are always included.
       // Use the locale of the site if not set in the config.
       // A locale is required to make the viewer working.
       if (empty($config['locales'])) {
           $locales = [
               'cy-CY' => 'cy-GB',
               'de-DE' => 'de-DE',
               'en-EN' => 'en-GB',
               'fr-FR' => 'fr-FR',
               'hr-HR' => 'hr-HR',
               'ja-JP' => 'ja-JP',
               'pl-PL' => 'pl-PL',
               'sv-SE' => 'sv-SE',
               'sv-SV' => 'sv-SE',
           ];
           $locale = $this->view->identity()
               ? (string) $this->view->userSetting('locale')
               : (string) $mainOrSiteSetting('locale');
           $locale = mb_strlen($locale) === 2
               ? mb_strtolower($locale) . '-' . mb_strtoupper($locale)
               : str_replace('_', '-', $locale);
           $locale = $locales[$locale] ?? 'en-GB';
           $config['locales'] = [
               ['name' => $locale],
           ];
       }

        $config += $options;

        return $this->view->partial('common/universal-viewer', [
            'config' => $config,
            'version' => '4',
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
            return $this->view->assetUrl($path, $module, false, false);
        }
    }
}
