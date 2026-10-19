<?php

namespace Limegreentangerine\Installers;

use RuntimeException;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvents;
use Composer\Script\Event as ScriptEvent;
use Limegreentangerine\Installers\Enums\Action;

class Plugin implements PluginInterface
{
    private Composer $composer;

    private bool $resourcesUninstalled = false;

    private function runClassKitResources(): void
    {
        $this->runVendorCommand('classkit-resources', Action::Install);
        $this->runVendorCommand('toolkit-resources', Action::Install);
    }

    private function runRemoveClassKitResources(): void
    {
        $this->runVendorCommand('classkit-resources', Action::Uninstall);
    }

    private function runVendorCommand(string $binary, Action $action, string ...$arguments): void
    {
        $vendorDir = $this->getVendorDir();
        $binaryPath = rtrim($vendorDir, '/\\') . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $action->value . '-' . $binary;
        $command = [PHP_BINARY, $binaryPath, ...$arguments];
        $projectRoot = getcwd();

        if ($projectRoot === false) {
            throw new RuntimeException('Unable to determine the Composer project directory.');
        }

        // The concrete CLI detects DIR_BASE from the cwd, so it must run from the web root.
        if ($binary === 'concrete') {
            foreach (['public', 'web'] as $webRoot) {
                $candidate = $projectRoot . DIRECTORY_SEPARATOR . $webRoot;
                if (is_file($candidate . DIRECTORY_SEPARATOR . 'index.php')) {
                    $projectRoot = $candidate;
                    break;
                }
            }
        }

        $process = proc_open(
            $command,
            [0 => STDIN, 1 => STDOUT, 2 => STDERR],
            $pipes,
            $projectRoot,
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

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $eventDispatcher = $composer->getEventDispatcher();
        $eventDispatcher->addListener(PackageEvents::POST_PACKAGE_INSTALL, [$this, 'onPostPackageInstall']);
        $eventDispatcher->addListener(PackageEvents::PRE_PACKAGE_UNINSTALL, [$this, 'onPrePackageUninstall']);
        $eventDispatcher->addListener(ScriptEvents::POST_UPDATE_CMD, [$this, 'onPostUpdate']);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        $eventDispatcher = $composer->getEventDispatcher();
        $eventDispatcher->removeListener(PackageEvents::POST_PACKAGE_INSTALL, [$this, 'onPostPackageInstall']);
        $eventDispatcher->removeListener(PackageEvents::PRE_PACKAGE_UNINSTALL, [$this, 'onPrePackageUninstall']);
        $eventDispatcher->removeListener(ScriptEvents::POST_UPDATE_CMD, [$this, 'onPostUpdate']);
    }

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public function onPostPackageInstall(PackageEvent $event): void
    {
        if ($event->getOperation()->getPackage()->getName() !== 'limegreentangerine/class_kit') {
            return;
        }

        $this->runClassKitResources();
    }

    public function onPrePackageUninstall(PackageEvent $event): void
    {
        $name = $event->getOperation()->getPackage()->getName();

        // class_kit is a dependency of this plugin, so the plugin is usually deactivated
        // before class_kit's uninstall event fires; its own uninstall event is the reliable trigger.
        if ($this->resourcesUninstalled || !in_array($name, ['limegreentangerine/installers', 'limegreentangerine/class_kit'], true)) {
            return;
        }

        $this->resourcesUninstalled = true;
        $this->runVendorCommand('classkit-resources', Action::Uninstall);
    }

    public function onPostUpdate(ScriptEvent $event): void
    {
        $this->runClassKitResources();
    }
}
