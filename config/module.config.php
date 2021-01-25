<?php declare(strict_types=1);

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
        // The aliases simplify the routing, the url assembly and allows to support module Clean url.
        'aliases' => [
            'UniversalViewer\Controller\Item' => Controller\PlayerController::class,
            'UniversalViewer\Controller\ItemSet' => Controller\PlayerController::class,
            'UniversalViewer\Controller\CleanUrlController' => Controller\PlayerController::class,
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    // This route allows to have a url compatible with Clean url.
                    'resource-id' => [
                        'may_terminate' => true,
                        'child_routes' => [
                            'universal-viewer' => [
                                'type' => \Laminas\Router\Http\Literal::class,
                                'options' => [
                                    'route' => '/uv',
                                    'constraints' => [
                                        'controller' => 'item|item-set',
                                        'action' => 'play',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'UniversalViewer\Controller',
                                        'controller' => 'Player',
                                        'action' => 'play',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // This route is the default url.
                    'resource-id-universal-viewer' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/:controller/:id/uv',
                            'constraints' => [
                                'controller' => 'item|item-set',
                                'action' => 'play',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'UniversalViewer\Controller',
                                'controller' => 'Player',
                                'action' => 'play',
                                'id' => '\d+',
                            ],
                        ],
                    ],
                ],
            ],
            // This route allows to have a top url without Clean url.
            // TODO Remove this route?
            'universalviewer_player' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/:controller/:id/uv',
                    'constraints' => [
                        'controller' => 'item|item-set',
                        'id' => '\d+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'UniversalViewer\Controller',
                        // '__SITE__' => true,
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
            //         'route' => '/:controller/play/:id',
            //         'constraints' => [
            //             'controller' => 'item|items|item\-set|item_set|collection|item\-sets|item_sets|collections',
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
    ],
];
