<?php declare(strict_types=1);

namespace UniversalViewer;

use Common\Stdlib\PsrMessage;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\View\Helper\Url $url
 * @var \Laminas\Log\Logger $logger
 * @var \Omeka\Settings\Settings $settings
 * @var \Laminas\I18n\View\Helper\Translate $translate
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Laminas\Mvc\I18n\Translator $translator
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Settings\SiteSettings $siteSettings
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$url = $plugins->get('url');
$api = $plugins->get('api');
$logger = $services->get('Omeka\Logger');
$settings = $services->get('Omeka\Settings');
$translate = $plugins->get('translate');
$translator = $services->get('MvcTranslator');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$siteSettings = $services->get('Omeka\Settings\Site');
$entityManager = $services->get('Omeka\EntityManager');

$defaultConfig = require dirname(__DIR__, 2) . '/config/module.config.php';

if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.80')) {
    $message = new \Omeka\Stdlib\Message(
        $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
        'Common', '3.4.80'
    );
    $messenger->addError($message);
    throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $translate('Missing requirement. Unable to upgrade.')); // @translate
}

if (version_compare($oldVersion, '3.4.1', '<')) {
    $defaultSettings = $defaultConfig['universalviewer']['config'];
    $defaultSiteSettings = $defaultConfig['universalviewer']['site_settings'];

    $settings->set(
        'universalviewer_manifest_description_property',
        @$defaultSettings['universalviewer_manifest_description_property']
    );

    $settings->set(
        'universalviewer_manifest_attribution_property',
        @$defaultSettings['universalviewer_manifest_attribution_property']
    );

    $settings->set(
        'universalviewer_manifest_attribution_default',
        $settings->get('universalviewer_attribution')
    );
    $settings->delete('universalviewer_attribution');

    $settings->set(
        'universalviewer_manifest_license_property',
        @$defaultSettings['universalviewer_manifest_license_property']
    );

    $settings->set(
        'universalviewer_manifest_license_default',
        $settings->get('universalviewer_licence')
    );
    $settings->delete('universalviewer_licence');

    $settings->set(
        'universalviewer_manifest_logo_default',
        @$defaultSettings['universalviewer_manifest_logo_default']
    );

    $settings->set(
        'universalviewer_append_item_set_show',
        $settings->get('universalviewer_append_collections_show')
    );
    $settings->delete('universalviewer_append_collections_show');

    $settings->set(
        'universalviewer_append_item_show',
        $settings->get('universalviewer_append_items_show')
    );
    $settings->delete('universalviewer_append_items_show');

    $settings->set(
        'universalviewer_append_item_set_browse',
        $defaultSiteSettings['universalviewer_append_item_set_browse']
    );

    $settings->set(
        'universalviewer_append_item_browse',
        $defaultSiteSettings['universalviewer_append_item_browse']
    );

    $style = $defaultSiteSettings['universalviewer_style'];
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

    $settings->set(
        'universalviewer_iiif_max_size',
        $settings->get('universalviewer_max_dynamic_size')
    );
    $settings->delete('universalviewer_max_dynamic_size');
}

if (version_compare($oldVersion, '3.5', '<=')
    && version_compare($newVersion, '3.5', '>=')
) {
    $settings->set(
        'universalviewer_manifest_property',
        $settings->get('universalviewer_alternative_manifest_property')
    );

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

if (version_compare($oldVersion, '3.5.2', '<=')) {
    $siteSettings = $services->get('Omeka\Settings\Site');
    $defaultSettings = $defaultConfig['universalviewer']['config'];
    $defaultSiteSettings = $defaultConfig['universalviewer']['site_settings'];

    $sites = $api->search('sites')->getContent();
    foreach ($sites as $site) {
        $siteSettings->setTargetId($site->id());
        foreach ($defaultSiteSettings as $name => $value) {
            $value = $settings->get($name);
            $siteSettings->set($name, $value);
        }
    }

    foreach ($defaultSiteSettings as $name => $value) {
        $settings->delete($name);
    }
}

if (version_compare($oldVersion, '3.6.0', '<')) {
    $sql = <<<SQL
        DELETE FROM site_setting
        WHERE id IN ("universalviewer_class", "universalviewer_style", "universalviewer_locale");
        SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.6.1', '<')) {
    $sql = <<<'SQL'
        DELETE FROM site_setting
        WHERE id IN ("universalviewer_append_item_set_show", "universalviewer_append_item_show", "universalviewer_append_item_set_browse", "universalviewer_append_item_browse");
        SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.6.3.0', '<')) {
    $settings->delete('universalviewer_manifest_property');
}

if (version_compare($oldVersion, '3.6.5.4', '<')) {
    $message = new PsrMessage(
        'Last version of Universal Viewer (v4) has been integrated. Check if it works fine with your documents.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.6.9', '<')) {
    /** @var \Omeka\Settings\SiteSettings $siteSettings */
    $siteSettings = $services->get('Omeka\Settings\Site');
    $sites = $api->search('sites')->getContent();
    foreach ($sites as $site) {
        $siteSettings->setTargetId($site->id());
        if ((string) $siteSettings->get('universalviewer_version', '4') === '4') {
            $siteSettings->set('universalviewer_config_theme', true);
        }
    }

    $message = new PsrMessage(
        'A param in settings (default) and in site settings allows to set the config of Universal Viewer version 4. See {link}documentation{link_end}.', // @translate
        ['link' => '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-UniversalViewer#exemple-of-full-config-for-version-4" target="_blank" rel="noopener">', 'link_end' => '</a>']
    );
    $message->setEscapeHtml(false);
    $messenger->addSuccess($message);
}
