<?php declare(strict_types=1);

namespace UniversalViewer\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;

class SiteSettingsFieldset extends SettingsFieldset
{
    protected $elementGroups = [
        'player' => 'Players', // @translate
        'themes_old' => 'Old themes', // @translate
    ];

    public function init(): void
    {
        parent::init();
        $this
            ->add([
                'name' => 'universalviewer_placement',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'themes_old',
                    'label' => 'Display Universal Viewer (old themes)', // @translate
                    'value_options' => [
                        'after/items' => 'Item show', // @translate
                        'browse/items' => 'Item browse', // @translate
                        'browse/item_sets' => 'Item set browse', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'universalviewer_placement',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'universalviewer_config_theme',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'player',
                    'label' => 'Universal Viewer: Use config file from the theme for v4 (deprecated)', // @translate
                ],
                'attributes' => [
                    'id' => 'universalviewer_config_theme',
                ],
            ])
        ;
    }
}
