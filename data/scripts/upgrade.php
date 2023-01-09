<?php declare(strict_types=1);

namespace UniversalViewer;

use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

$defaultConfig = require dirname(__DIR__, 2) . '/config/module.config.php';

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
