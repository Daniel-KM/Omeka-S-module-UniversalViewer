<?php declare(strict_types=1);

namespace UniversalViewer\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class UniversalViewer implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Universal Viewer'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
            'media',
            'item_sets',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/universal-viewer', [
            'resource' => $resource,
        ]);
    }
}
