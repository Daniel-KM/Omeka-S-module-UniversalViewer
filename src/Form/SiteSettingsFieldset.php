<?php declare(strict_types=1);

namespace UniversalViewer\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = 'Universal Viewer'; // @translate

    protected $elementGroups = [
        // "Player" is used instead of viewer, because "viewer" is used for a site
        // user role and cannot be translated differently (no context).
        // Player is polysemic too anyway, but less used and more adapted for
        // non-image viewers.
        'player' => 'Players', // @translate
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'universal-viewer')
            ->setOption('element_groups', $this->elementGroups)
            ->add([
                'name' => 'universalviewer_version',
                'type' => Element\Radio::class,
                'options' => [
                    'element_group' => 'player',
                    'label' => 'Version of Universal viewer', // @translate
                    'value_options' => [
                        '2' => 'Version 2.0.2 (better speed for some scanned pdf; require iiif v2)', // @translate
                        '3' => 'Version 3.1.1 (more modern)', // @translate
                        '4' => 'Version 4 (up to date)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'universalviewer_version',
                    'value' => '4',
                ],
            ])
        ;
    }
}
