<?php

namespace OrApp\OrCmfBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use OrApp\OrCmfBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OrAppOrCmfBundle extends Bundle
{
    
     /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SfynxCmfBundle';
    }
    
    /**
     * Builds the bundle.
     *
     * It is only ever called once when the cache is empty.
     *
     * This method can be overridden to register compilation passes,
     * other extensions, ...
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }
    
    /**
     * Boots the Bundle.
     */
    public function boot()
    {
    }    
    
    /**
     * Shutdowns the Bundle.
     */
    public function shutdown()
    {
    }    
    
}
