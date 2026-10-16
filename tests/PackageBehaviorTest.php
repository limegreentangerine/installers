<?php

declare(strict_types=1);

require_once __DIR__ . '/ComposerStubs.php';

use Composer\Config;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\EventDispatcher;
use Composer\Installer\Package;
use PHPUnit\Framework\TestCase;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Installer\PackageOperation;
use Composer\Script\Event as ScriptEvent;
use Limegreentangerine\Installers\Plugin;

final class PackageBehaviorTest extends TestCase
{
    private string $temporaryDirectory;

    private string $originalDirectory;

    private function createPlugin(string $commandBody = "file_put_contents(__DIR__ . '/../calls.log', basename(__FILE__) . PHP_EOL, FILE_APPEND);\n"): Plugin
    {
        $vendorDirectory = $this->temporaryDirectory . '/vendor';
        if (!is_dir($vendorDirectory . '/bin')) {
            mkdir($vendorDirectory . '/bin', 0755, true);
        }
        foreach (['classkit-resources', 'uninstall-classkit-resources'] as $binary) {
            file_put_contents($vendorDirectory . '/bin/' . $binary, "<?php\n" . $commandBody);
        }

        $plugin = new Plugin();
        $plugin->activate(
            new Composer(new Config($vendorDirectory), new EventDispatcher()),
            new class implements IOInterface {},
        );
        chdir($this->temporaryDirectory);

        return $plugin;
    }

    private function packageEvent(string $name): PackageEvent
    {
        return new PackageEvent(new PackageOperation(new Package($name)));
    }

    private function createNpmStub(): array
    {
        $directory = $this->temporaryDirectory . '/bin';
        mkdir($directory);
        $log = $this->temporaryDirectory . '/npm.log';
        $script = $directory . '/npm';
        file_put_contents(
            $script,
            "#!/bin/sh\nprintf '%s\\t%s\\n' \"\$PWD\" \"\$*\" >> \"\$INSTALLERS_NPM_LOG\"\nexit \"\${INSTALLERS_NPM_EXIT:-0}\"\n",
        );
        chmod($script, 0755);

        return ['directory' => $directory, 'log' => $log];
    }

    private function runScript(string $script, string $workingDirectory, string $input = '', array $environment = []): array
    {
        $process = proc_open(
            [PHP_BINARY, $script],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $workingDirectory,
            $environment === [] ? null : $environment,
        );
        self::assertIsResource($process);
        fwrite($pipes[0], $input);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return ['exitCode' => $exitCode, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }

    public function testToolkitResourcesCreatesHtaccessAndLeavesExistingFileUntouched(): void
    {
        $project = $this->temporaryDirectory . '/project';
        mkdir($project . '/public', 0755, true);

        $result = $this->runScript(__DIR__ . '/../bin/toolkit-resources', $project);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $htaccess = $project . '/public/.htaccess';
        self::assertFileExists($htaccess);
        self::assertStringContainsString('RewriteRule . index.php [L]', file_get_contents($htaccess));

        file_put_contents($htaccess, 'custom rules');
        $result = $this->runScript(__DIR__ . '/../bin/toolkit-resources', $project);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertSame('custom rules', file_get_contents($htaccess));
        self::assertStringContainsString('Skipped (already exists)', $result['stdout']);
    }

    public function testClassKitResourcesRunsBothNpmInstallCommandsInProjectRoot(): void
    {
        $project = $this->temporaryDirectory . '/project';
        mkdir($project);
        $npm = $this->createNpmStub();

        $result = $this->runScript(
            __DIR__ . '/../bin/classkit-resources',
            $project,
            '',
            ['PATH' => $npm['directory'] . ':' . (getenv('PATH') ?: ''), 'INSTALLERS_NPM_LOG' => $npm['log']],
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertSame([
            realpath($project) . "\tinstall --save-dev typescript ts-loader @types/node svelte svelte-loader",
            realpath($project) . "\tinstall --save bootstrap bootstrap-icons @popperjs/core @types/bootstrap",
        ], file($npm['log'], FILE_IGNORE_NEW_LINES));
    }

    public function testClassKitResourcesPropagatesNpmFailureAndStops(): void
    {
        $project = $this->temporaryDirectory . '/project';
        mkdir($project);
        $npm = $this->createNpmStub();

        $result = $this->runScript(
            __DIR__ . '/../bin/classkit-resources',
            $project,
            '',
            [
                'PATH' => $npm['directory'] . ':' . (getenv('PATH') ?: ''),
                'INSTALLERS_NPM_LOG' => $npm['log'],
                'INSTALLERS_NPM_EXIT' => '23',
            ],
        );

        self::assertSame(23, $result['exitCode']);
        self::assertCount(1, file($npm['log'], FILE_IGNORE_NEW_LINES));
    }

    public function testUninstallClassKitResourcesRunsNpmUninstall(): void
    {
        $project = $this->temporaryDirectory . '/project';
        mkdir($project);
        $npm = $this->createNpmStub();

        $result = $this->runScript(
            __DIR__ . '/../bin/uninstall-classkit-resources',
            $project,
            '',
            ['PATH' => $npm['directory'] . ':' . (getenv('PATH') ?: ''), 'INSTALLERS_NPM_LOG' => $npm['log']],
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertSame([
            realpath($project) . "\tuninstall --save-dev typescript ts-loader @types/node svelte svelte-loader",
        ], file($npm['log'], FILE_IGNORE_NEW_LINES));
    }

    public function testActivateAndDeactivateRegisterAndRemovePluginListeners(): void
    {
        $plugin = new Plugin();
        $dispatcher = new EventDispatcher();
        $composer = new Composer(new Config($this->temporaryDirectory . '/vendor'), $dispatcher);
        $io = new class implements IOInterface {};

        $plugin->activate($composer, $io);

        self::assertCount(1, $dispatcher->getListeners(PackageEvents::POST_PACKAGE_INSTALL));
        self::assertCount(1, $dispatcher->getListeners(PackageEvents::PRE_PACKAGE_UNINSTALL));
        self::assertCount(1, $dispatcher->getListeners(ScriptEvents::POST_UPDATE_CMD));

        $plugin->deactivate($composer, $io);

        self::assertCount(0, $dispatcher->getListeners(PackageEvents::POST_PACKAGE_INSTALL));
        self::assertCount(0, $dispatcher->getListeners(PackageEvents::PRE_PACKAGE_UNINSTALL));
        self::assertCount(0, $dispatcher->getListeners(ScriptEvents::POST_UPDATE_CMD));
    }

    public function testPluginRunsClassKitCommandForItsInstallAndPostUpdateEvents(): void
    {
        $plugin = $this->createPlugin();

        $plugin->onPostPackageInstall($this->packageEvent('another/package'));
        self::assertFileDoesNotExist($this->temporaryDirectory . '/vendor/calls.log');

        $plugin->onPostPackageInstall($this->packageEvent('limegreentangerine/class_kit'));
        $plugin->onPostUpdate(new ScriptEvent());

        self::assertSame(
            "classkit-resources\nclasskit-resources\n",
            file_get_contents($this->temporaryDirectory . '/vendor/calls.log'),
        );
    }

    public function testPluginUninstallsResourcesOnceForEitherRelatedPackage(): void
    {
        $plugin = $this->createPlugin();

        $plugin->onPrePackageUninstall($this->packageEvent('limegreentangerine/installers'));
        $plugin->onPrePackageUninstall($this->packageEvent('limegreentangerine/class_kit'));
        $plugin->onPrePackageUninstall($this->packageEvent('another/package'));

        self::assertSame(
            "uninstall-classkit-resources\n",
            file_get_contents($this->temporaryDirectory . '/vendor/calls.log'),
        );

        $anotherPlugin = $this->createPlugin();
        $anotherPlugin->onPrePackageUninstall($this->packageEvent('limegreentangerine/class_kit'));

        self::assertSame(
            "uninstall-classkit-resources\nuninstall-classkit-resources\n",
            file_get_contents($this->temporaryDirectory . '/vendor/calls.log'),
        );
    }

    public function testPluginSurfacesCommandFailures(): void
    {
        $plugin = $this->createPlugin("exit(9);\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Command "classkit-resources" failed with exit code 9.');

        $plugin->onPostUpdate(new ScriptEvent());
    }

    protected function setUp(): void
    {
        $this->originalDirectory = getcwd();
        $this->temporaryDirectory = sys_get_temp_dir() . '/installers-tests-' . bin2hex(random_bytes(8));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        chdir($this->originalDirectory);
        $this->removeDirectory($this->temporaryDirectory);
    }
}
