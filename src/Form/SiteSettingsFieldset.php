<?php declare(strict_types=1);

namespace UniversalViewer\Form;

use Laminas\Form\Element;

class SiteSettingsFieldset extends SettingsFieldset
{
    public function init(): void
    {
        parent::init();
        $this
            ->add([
                'name' => 'universalviewer_config_theme',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'player',
                    'label' => 'Use Universal Viewer config from the theme for v4 (deprecated)', // @translate
                ],
                'attributes' => [
                    'id' => 'universalviewer_config_theme',
                ],
            ])
        ;
    }
}
