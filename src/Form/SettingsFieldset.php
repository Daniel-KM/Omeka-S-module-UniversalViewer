<?php declare(strict_types=1);
namespace UniversalViewer\Form;

use Laminas\Form\Fieldset;
use Omeka\Form\Element\PropertySelect;

class SettingsFieldset extends Fieldset
{
    protected $label = 'Universal Viewer'; // @translate

    public function init(): void
    {
        $this
            ->add([
                'name' => 'universalviewer_manifest_property',
                'type' => PropertySelect::class,
                'options' => [
                    'label' => 'Manifest property', // @translate
                    'info' => 'The property supplying the manifest URL for the viewer, for example "dcterms:hasFormat" or "dcterms:isFormatOf".', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'universalviewer_manifest_property',
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                ],
            ]);
    }
}
