<?php

namespace Limegreentangerine\Installers;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;
use RuntimeException;

class Plugin implements PluginInterface
{
    private Composer $composer;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $eventDispatcher = $composer->getEventDispatcher();
        $eventDispatcher->addListener(PackageEvents::POST_PACKAGE_INSTALL, [$this, 'onPostPackageInstall']);
        $eventDispatcher->addListener(ScriptEvents::POST_UPDATE_CMD, [$this, 'onPostUpdate']);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        $eventDispatcher = $composer->getEventDispatcher();
        $eventDispatcher->removeListener(PackageEvents::POST_PACKAGE_INSTALL, [$this, 'onPostPackageInstall']);
        $eventDispatcher->removeListener(ScriptEvents::POST_UPDATE_CMD, [$this, 'onPostUpdate']);
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $eventDispatcher = $composer->getEventDispatcher();
        $eventDispatcher->addListener(PackageEvents::PRE_PACKAGE_UNINSTALL, [$this, 'onPrePackageUninstall']);
    }

    public function onPostPackageInstall(PackageEvent $event): void
    {
        if ($event->getOperation()->getPackage()->getName() !== 'limegreentangerine/class_kit') {
            return;
        }

        $this->runClassKitResources();
        $this->runConcreteCommand('c5:package:install', 'class_kit');
    }

    public function onPostUpdate(ScriptEvent $event): void
    {
        $this->runClassKitResources();
        $this->runConcreteCommand('c5:package:update', 'class_kit');
    }

    public function onPrePackageUninstall(PackageEvent $event): void
    {
        if ($event->getOperation()->getPackage()->getName() !== 'limegreentangerine/class_kit') {
            return;
        }

        $this->runConcreteCommand('c5:package:uninstall', 'class_kit');
        $this->runRemoveClassKitResources();
    }

    private function runClassKitResources(): void
    {
        $this->runVendorCommand('classkit-resources');
    }

    private function runRemoveClassKitResources(): void
    {
        $this->runVendorCommand('uninstall-classkit-resources');
    }

    private function runConcreteCommand(string $command, string $handle): void
    {
        $this->runVendorCommand('concrete', $command, $handle);
    }

    private function runVendorCommand(string $binary, string ...$arguments): void
    {
        $vendorDir = $this->getVendorDir();
        $binaryPath = rtrim($vendorDir, '/\\') . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $binary;
        $command = [PHP_BINARY, $binaryPath, ...$arguments];
        $projectRoot = getcwd();

        if ($projectRoot === false) {
            throw new RuntimeException('Unable to determine the Composer project directory.');
        }

        $process = proc_open(
            $command,
            [0 => STDIN, 1 => STDOUT, 2 => STDERR],
            $pipes,
            $projectRoot
        );

        if (!is_resource($process)) {
            throw new RuntimeException(sprintf('Unable to start command "%s".', $binary));
        }

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf('Command "%s" failed with exit code %d.', $binary, $exitCode));
        }
    }

    private function getVendorDir(): string
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');

        if (!is_string($vendorDir) || $vendorDir === '') {
            throw new RuntimeException('Composer did not provide a valid vendor directory.');
        }

        return $vendorDir;
    }
}
