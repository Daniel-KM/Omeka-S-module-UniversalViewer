<?php declare(strict_types=1);

namespace UniversalViewer\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = 'Universal Viewer'; // @translate

    public function init(): void
    {
        $this
            ->add([
                'name' => 'universalviewer_version',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Version of Universal viewer', // @translate
                    'value_options' => [
                        '2' => 'Version 2.0.2 (better speed for some scanned pdf; require iiif v2)', // @translate
                        '3' => 'Version 3 (more modern)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'search_main_page',
                    'value' => '3',
                ],
            ])
            ->add([
                'name' => 'universalviewer_append_to_item_view_show',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Show UniversalViewer on item page', // @translate
                    'use_hidden_element' => true,
                ],
            ])
            ->add([
                'name' => 'universalviewer_append_to_item_view_browse',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Show UniversalViewer on item browse page', // @translate
                    'use_hidden_element' => true,
                ],
            ])
            ->add([
                'name' => 'universalviewer_append_to_itemset_view_browse',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Show UniversalViewer on item set browse page', // @translate
                    'use_hidden_element' => true,
                ],
            ])
        ;
    }
}
