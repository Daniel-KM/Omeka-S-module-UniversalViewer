<?php
namespace UniversalViewer\Form;

use Omeka\Api\Manager as ApiManager;
use Omeka\Settings\Settings;
use Zend\Form\Form;

class Config extends Form
{
    protected $api;
    protected $settings;
    protected $iiifServerIsActive;

    public function init()
    {
        $settings = $this->getSettings();
        $properties = $this->listProperties();

        $this->add([
            'type' => 'Select',
            'name' => 'universalviewer_manifest_property',
            'options' => [
                'label' => 'Manifest property', // @translate
                'info' => 'The property supplying the manifest URL for the viewer, for example "dcterms:hasFormat".', // @translate
                'empty_option' => 'Select a property...', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'value' => $settings->get('universalviewer_manifest_property'),
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
     * @param bool $iiifServerIsActive
     */
    public function setIiifServerIsActive($iiifServerIsActive)
    {
        $this->iiifServerIsActive = $iiifServerIsActive;
    }

    /**
     * @return bool
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
