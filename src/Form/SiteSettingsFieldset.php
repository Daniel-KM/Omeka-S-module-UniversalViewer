<?php
namespace UniversalViewer\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    public function init()
    {
        // The module iiif server is required to display collections of items.
        $iiifServerIsActive = $this->getIiifServerIsActive();

        $this->setLabel('Universal Viewer'); // @translate

        $this->add([
            'name' => 'universalviewer_append_item_set_show',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item set page', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'id' => 'universalviewer_append_item_set_show',
            ],
        ]);

        $this->add([
            'name' => 'universalviewer_append_item_show',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item page', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'id' => 'universalviewer_append_item_show',
            ],
        ]);

        $this->add([
            'name' => 'universalviewer_append_item_set_browse',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item sets browse page', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'id' => 'universalviewer_append_item_set_browse',
                'disabled' => !$iiifServerIsActive,
            ],
        ]);

        $this->add([
            'name' => 'universalviewer_append_item_browse',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item browse page', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'id' => 'universalviewer_append_item_browse',
                'disabled' => !$iiifServerIsActive,
            ],
        ]);

        $this->add([
            'name' => 'universalviewer_class',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Class of main div', // @translate
                'info' => 'Class to add to the main div.',  // @translate
            ],
            'attributes' => [
                'id' => 'universalviewer_class',
            ],
        ]);

        $this->add([
            'name' => 'universalviewer_style',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Inline style', // @translate
                'info' => 'If any, this style will be added to the main div of the Universal Viewer.' // @translate
                . ' ' . 'The height may be required.', // @translate
            ],
            'attributes' => [
                'id' => 'universalviewer_style',
            ],
        ]);

        $this->add([
            'name' => 'universalviewer_locale',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Locales of the viewer', // @translate
                'info' => 'Currently not working', // @translate
            ],
            'attributes' => [
                'id' => 'universalviewer_locale',
            ],
        ]);
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
}
