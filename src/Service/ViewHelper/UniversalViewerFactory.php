<?php declare(strict_types=1);

namespace UniversalViewer\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UniversalViewer\View\Helper\UniversalViewer;

/**
 * Service factory for the UniversalViewer view helper.
 */
class UniversalViewerFactory implements FactoryInterface
{
    /**
     * Create and return the UniversalViewer view helper
     *
     * @return UniversalViewer
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $currentTheme = $services->get('Omeka\Site\ThemeManager')
            ->getCurrentTheme();
        return new UniversalViewer($currentTheme);
    }
}
