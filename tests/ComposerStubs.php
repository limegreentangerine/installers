<?php

namespace Composer\Plugin;

interface PluginInterface
{
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io): void;

    public function deactivate(\Composer\Composer $composer, \Composer\IO\IOInterface $io): void;

    public function uninstall(\Composer\Composer $composer, \Composer\IO\IOInterface $io): void;
}

namespace Composer\IO;

interface IOInterface {}

namespace Composer;

class Config
{
    public function __construct(private mixed $vendorDir) {}

    public function get(string $key): mixed
    {
        return $key === 'vendor-dir' ? $this->vendorDir : null;
    }
}

class EventDispatcher
{
    private array $listeners = [];

    public function addListener(string $event, array $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function removeListener(string $event, array $listener): void
    {
        $this->listeners[$event] = array_values(array_filter(
            $this->listeners[$event] ?? [],
            static fn(array $registered): bool => $registered !== $listener,
        ));
    }

    public function getListeners(string $event): array
    {
        return $this->listeners[$event] ?? [];
    }
}

class Composer
{
    public function __construct(
        private Config $config,
        private EventDispatcher $eventDispatcher,
    ) {}

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }
}

namespace Composer\Installer;

class PackageEvents
{
    public const POST_PACKAGE_INSTALL = 'post-package-install';
    public const PRE_PACKAGE_UNINSTALL = 'pre-package-uninstall';
}

class PackageEvent
{
    public function __construct(private PackageOperation $operation) {}

    public function getOperation(): PackageOperation
    {
        return $this->operation;
    }
}

class PackageOperation
{
    public function __construct(private Package $package) {}

    public function getPackage(): Package
    {
        return $this->package;
    }
}

class Package
{
    public function __construct(private string $name) {}

    public function getName(): string
    {
        return $this->name;
    }
}

namespace Composer\Script;

class ScriptEvents
{
    public const POST_UPDATE_CMD = 'post-update-cmd';
}

class Event {}
