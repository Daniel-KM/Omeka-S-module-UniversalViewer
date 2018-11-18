<?php
namespace UniversalViewer;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'universalViewer' => Service\ViewHelper\UniversalViewerFactory::class,
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'universalViewer' => Site\BlockLayout\UniversalViewer::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'UniversalViewer\Controller\Player' => Controller\PlayerController::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
        ],
        'factories' => [
            Form\SiteSettingsFieldset::class => Service\Form\SiteSettingsFieldsetFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'universalviewer_player' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/:resourcename/:id/play',
                    'constraints' => [
                        'resourcename' => 'item|item\-set',
                        'id' => '\d+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'UniversalViewer\Controller',
                        'controller' => 'Player',
                        'action' => 'play',
                    ],
                ],
            ],

            // If really needed, the next route may be uncommented to keep
            // compatibility with the old schemes used by the plugin for Omeka 2
            // before the version 2.4.2.
            // 'universalviewer_player_classic' => [
            //     'type' => 'segment',
            //     'options' => [
            //         'route' => '/:resourcename/play/:id',
            //         'constraints' => [
            //             'resourcename' => 'item|items|item\-set|item_set|collection|item\-sets|item_sets|collections',
            //             'id' => '\d+',
            //         ],
            //         'defaults' => [
            //             '__NAMESPACE__' => 'UniversalViewer\Controller',
            //             'controller' => 'Player',
            //             'action' => 'play',
            //         ],
            //     ],
            // ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'universalviewer' => [
        'config' => [
            'universalviewer_manifest_property' => '',
        ],
        'site_settings' => [
            'universalviewer_append_item_set_show' => true,
            'universalviewer_append_item_show' => true,
            'universalviewer_append_item_set_browse' => false,
            'universalviewer_append_item_browse' => false,
            'universalviewer_class' => '',
            'universalviewer_style' => 'background-color: #000; height: 600px;',
            'universalviewer_locale' => 'en-GB:English (GB),fr:French',
        ],
    ],
];
