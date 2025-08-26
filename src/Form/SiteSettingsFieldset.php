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
                'name' => 'universalviewer_show_browse',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'player',
                    'label' => 'Show Universal Viewer on item browse pages', // @translate
                    'info' => 'Display Universal Viewer for collection items on browse pages (requires IIIF Server module for collections)', // @translate
                ],
                'attributes' => [
                    'id' => 'universalviewer_show_browse',
                    'value' => 1,
                    'checked' => true,
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
