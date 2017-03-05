<?php
namespace UniversalViewer\Form;

use Omeka\Api\Manager as ApiManager;
use Omeka\Settings\Settings;
use Zend\Form\Form;

class ConfigForm extends Form
{

    protected $api;
    protected $settings;
    protected $iiifServerIsActive;

    public function init()
    {
        $settings = $this->getSettings();
        $properties = $this->listProperties();
        $iiifServerIsActive = $this->getIiifServerIsActive();

        $info = $iiifServerIsActive
            ? 'The IIIF Server is active, so when no url is set, the viewer will use the standard routes.' // @translate
            : 'The IIIF Server is not active, so when no url is set, the viewer won’t be displayed.'; // @translate
        $this->add([
            'type' => 'Fieldset',
            'name' => 'universalviewer_manifest',
            'options' => [
                'label' => 'IIIF Manifests', // @translate
                'info' => 'The module uses an url to fetch data and medias to display.' // @translate
                    . ' ' . $info,
            ],
        ]);
        $manifestFieldset = $this->get('universalviewer_manifest');

        $manifestFieldset->add([
            'type' => 'Select',
            'name' => 'universalviewer_manifest_property',
            'options' => [
                'label' => 'Manifest Property', // @translate
                'info' => 'The property supplying the manifest URL for the viewer, for example "dcterms:hasFormat".', // @translate
                'empty_option' => 'Select a property...', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'value' => $settings->get('universalviewer_manifest_property'),
            ],
        ]);

        $info = $iiifServerIsActive
            ? ''
            : 'The IIIF Server is not active, so the Universal Viewer can’t display lists.'; // @translate
        $this->add([
            'type' => 'Fieldset',
            'name' => 'universalviewer_integration',
            'options' => [
                'label' => 'Integration of the viewer', // @translate
                'info' => 'If checked, the viewer will be automatically appended to the item sets or items pages.' // @translate
                    . ' ' . 'Else, the viewer can be added via the helper in the theme or the shortcode in any page.' // @translate
                    . ' ' . $info,
            ],
        ]);
        $integrationFieldset = $this->get('universalviewer_integration');

        $integrationFieldset->add([
            'name' => 'universalviewer_append_item_set_show',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Append to "Item set show"', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('universalviewer_append_item_set_show'),
            ],
        ]);

        $integrationFieldset->add([
            'name' => 'universalviewer_append_item_show',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Append to "Item show"', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('universalviewer_append_item_show'),
            ],
        ]);

        $integrationFieldset->add([
            'name' => 'universalviewer_append_item_set_browse',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Append to "Item set browse"', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('universalviewer_append_item_set_browse'),
                'disabled', !$iiifServerIsActive,
            ],
        ]);

        $integrationFieldset->add([
            'name' => 'universalviewer_append_item_browse',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Append to "Item browse"', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('universalviewer_append_item_browse'),
                'disabled', !$iiifServerIsActive,
            ],
        ]);

        $this->add([
            'type' => 'Fieldset',
            'name' => 'universalviewer_params',
            'options' => [
                'label' => 'Params of the viewer', // @translate
                'info' => 'These values allows to parameter the integration of the viewer in Omeka pages.' // @translate
                    . ' ' . 'The viewer itself can be configured via the file "config.json" and the helper.', // @translate
            ],
        ]);
        $paramsFieldset = $this->get('universalviewer_params');

        $paramsFieldset->add([
            'name' => 'universalviewer_class',
            'type' => 'Text',
            'options' => [
                'label' => 'Class of main div', // @translate
                'info' => 'Class to add to the main div.',  // @translate
            ],
            'attributes' => [
                'value' => $settings->get('universalviewer_class'),
            ],
        ]);

        $paramsFieldset->add([
            'name' => 'universalviewer_style',
            'type' => 'Text',
            'options' => [
                'label' => 'Inline style', // @translate
                'info' => 'If any, this style will be added to the main div of the Universal Viewer.' // @translate
                    . ' ' . 'The height may be required.', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('universalviewer_style'),
            ],
        ]);

        $paramsFieldset->add([
            'name' => 'universalviewer_locale',
            'type' => 'Text',
            'options' => [
                'label' => 'Locales of the viewer', // @translate
            ],
            'attributes' => [
                'value' => $settings->get('universalviewer_locale'),
            ],
        ]);
    }

    /**
     * @param ApiManager $api
     */
    public function setApi(ApiManager $api)
    {
        $this->api = $api;
    }

    /**
     * @return ApiManager
     */
    protected function getApi()
    {
        return $this->api;
    }

    /**
     * @param Settings $settings
     */
    public function setSettings(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return Settings
     */
    protected function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param boolean $iiifServerIsActive
     */
    public function setIiifServerIsActive($iiifServerIsActive)
    {
        $this->iiifServerIsActive = $iiifServerIsActive;
    }

    /**
     * @return boolean
     */
    public function getIiifServerIsActive()
    {
        return $this->iiifServerIsActive;
    }

    /**
     * Helper to prepare the true list of properties (not the internal ids).
     *
     * @return array
     */
    protected function listProperties()
    {
        $properties = [];
        $response = $this->getApi()->search('vocabularies');
        foreach ($response->getContent() as $vocabulary) {
            $options = [];
            foreach ($vocabulary->properties() as $property) {
                $options[] = [
                    'label' => $property->label(),
                    'value' => $property->term(),
                ];
            }
            if (!$options) {
                continue;
            }
            $properties[] = [
                'label' => $vocabulary->label(),
                'options' => $options,
            ];
        }
        return $properties;
    }
}
