<?php

namespace spec\ContainerTools\Configuration;

use ContainerTools\Configuration;
use ContainerTools\Configuration\DelegatingLoaderFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class LoaderSpec extends ObjectBehavior
{
    function let(
        ContainerBuilder $containerBuilder,
        DelegatingLoaderFactory $delegatingLoaderFactory,
        Configuration $configuration,
        Filesystem $filesystem
    )
    {
        $this->beConstructedWith($containerBuilder, $delegatingLoaderFactory, $filesystem);

        $configuration->getServicesFormat()->willReturn('xml');
        $configuration->isTestEnvironment()->willReturn(false);
    }

    function it_loads_services_into_a_container(
        Configuration $configuration,
        ContainerBuilder $containerBuilder,
        DelegatingLoaderFactory $delegatingLoaderFactory,
        DelegatingLoader $delegatingLoader,
        Filesystem $filesystem
    ) {
        $configuration->getServicesFolders()->willReturn(['etc1']);

        $delegatingLoaderFactory->create($containerBuilder, 'etc1')->willReturn($delegatingLoader);
        $filesystem->exists('etc1/services.xml')->willReturn(true);

        $this->loadContainer($configuration)->shouldReturnAnInstanceOf(Container::class);

        $delegatingLoader->load('services.xml')->shouldHaveBeenCalled();
    }


    function it_skips_loading_services_if_none_exist(
        Configuration $configuration,
        ContainerBuilder $containerBuilder,
        DelegatingLoaderFactory $delegatingLoaderFactory,
        DelegatingLoader $delegatingLoader,
        Filesystem $filesystem
    ) {
        $configuration->getServicesFolders()->willReturn(['etc1']);

        $delegatingLoaderFactory->create($containerBuilder, 'etc1')->willReturn($delegatingLoader);
        $filesystem->exists('etc1/services.xml')->willReturn(false);

        $this->loadContainer($configuration)->shouldReturnAnInstanceOf(Container::class);

        $delegatingLoader->load('services.xml')->shouldNotHaveBeenCalled();
    }


    function it_loads_services_into_a_container_from_multiple_paths(
        Configuration $configuration,
        ContainerBuilder $containerBuilder,
        DelegatingLoaderFactory $delegatingLoaderFactory,
        DelegatingLoader $delegatingLoader1,
        DelegatingLoader $delegatingLoader2,
        Filesystem $filesystem
    ){
        $configuration->getServicesFolders()->willReturn(['etc1', 'etc2']);

        $delegatingLoaderFactory->create($containerBuilder, 'etc1')->willReturn($delegatingLoader1);
        $delegatingLoaderFactory->create($containerBuilder, 'etc2')->willReturn($delegatingLoader2);
        $filesystem->exists('etc1/services.xml')->willReturn(true);
        $filesystem->exists('etc2/services.xml')->willReturn(true);

        $this->loadContainer($configuration)->shouldReturnAnInstanceOf(Container::class);

        $delegatingLoader1->load('services.xml')->shouldHaveBeenCalled();
        $delegatingLoader2->load('services.xml')->shouldHaveBeenCalled();
    }

    function it_loads_services_into_a_container_including_test_services(
        Configuration $configuration,
        ContainerBuilder $containerBuilder,
        DelegatingLoaderFactory $delegatingLoaderFactory,
        DelegatingLoader $delegatingLoader1,
        DelegatingLoader $delegatingLoader2,
        Filesystem $filesystem
    ) {
        $configuration->getServicesFolders()->willReturn(['etc1', 'etc2']);
        $configuration->isTestEnvironment()->willReturn(true);

        $delegatingLoaderFactory->create($containerBuilder, 'etc1')->willReturn($delegatingLoader1);
        $delegatingLoaderFactory->create($containerBuilder, 'etc2')->willReturn($delegatingLoader2);
        $filesystem->exists('etc1/services.xml')->willReturn(true);
        $filesystem->exists('etc2/services.xml')->willReturn(true);
        $filesystem->exists('etc1/services_test.xml')->willReturn(true);
        $filesystem->exists('etc2/services_test.xml')->willReturn(true);

        $this->loadContainer($configuration)->shouldReturnAnInstanceOf(Container::class);

        $delegatingLoader1->load('services.xml')->shouldHaveBeenCalled();
        $delegatingLoader2->load('services.xml')->shouldHaveBeenCalled();

        $delegatingLoader1->load('services_test.xml')->shouldHaveBeenCalled();
        $delegatingLoader2->load('services_test.xml')->shouldHaveBeenCalled();
    }


    function it_skips_loading_test_services_if_none_exist(
        Configuration $configuration,
        ContainerBuilder $containerBuilder,
        DelegatingLoaderFactory $delegatingLoaderFactory,
        DelegatingLoader $delegatingLoader,
        Filesystem $filesystem
    ) {
        $configuration->getServicesFolders()->willReturn(['etc1']);
        $configuration->isTestEnvironment()->willReturn(true);

        $delegatingLoaderFactory->create($containerBuilder, 'etc1')->willReturn($delegatingLoader);
        $filesystem->exists('etc1/services.xml')->willReturn(true);
        $filesystem->exists('etc1/services_test.xml')->willReturn(false);

        $this->loadContainer($configuration)->shouldReturnAnInstanceOf(Container::class);

        $delegatingLoader->load('services.xml')->shouldHaveBeenCalled();

        $delegatingLoader->load('services_test.xml')->shouldNotHaveBeenCalled();
    }
}
