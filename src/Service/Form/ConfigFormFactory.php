<?php
namespace UniversalViewer\Service\Form;

use Interop\Container\ContainerInterface;
use UniversalViewer\Form\ConfigForm;
use Zend\ServiceManager\Factory\FactoryInterface;

class ConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $moduleManager = $container->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('IiifServer');
        $iiifServerIsActive = $module && $module->getState() == \Omeka\Module\Manager::STATE_ACTIVE;

        $form = new ConfigForm(null, $options);
        $form->setIiifServerIsActive($iiifServerIsActive);
        return $form;
    }
}
