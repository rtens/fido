<?php
namespace spec\watoki\fido\fixture;

use Composer\Cache;
use Composer\Command\UpdateCommand;
use Composer\Config;
use Composer\Downloader\FileDownloader;
use Composer\Downloader\GitDownloader;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Json\JsonFile;
use Composer\Package\Locker;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use watoki\fido\FidoPlugin;
use watoki\scrut\Fixture;

/**
 * @property FileFixture file <-
 */
class ComposerFixture extends Fixture {

    private $remoteFiles = array();

    private $json = array();

    /** @var TestProcessExecutorMock */
    private $executor;

    /** @var BufferIO */
    private $io;

    public function givenTheComposerJson($json) {
        $this->json = json_decode($json, true);
    }

    public function givenTheRemoteFile_Containing($fileUrl, $content) {
        $this->remoteFiles[$fileUrl] = $content;
    }

    public function whenIRunComposerWithThePlugin() {
        $factory = new Factory();
        $this->io = new BufferIO();

        $config = array_merge(array(
                'config' => array(
                        'vendor-dir' => __DIR__ . '/__tmp/vendor'
                ),
                'repositories' => array(
                        'packagist' => false
                )
        ), $this->json);

        $composer = $factory->createComposer($this->io, $config);

        $composer->setLocker(new Locker($this->io, new JsonFile('not-existing'),
                $composer->getRepositoryManager(), $composer->getInstallationManager(), ''));

        $this->executor = new TestProcessExecutorMock();
        $composer->getDownloadManager()->setDownloader('git', new GitDownloader($this->io, $composer->getConfig(), $this->executor));

        $cache = new Cache($this->io, __DIR__ . '/__tmp/vendor/cache');
        $rfs = new TestRemoteFileSystemMock($this->remoteFiles);

        $composer->getDownloadManager()->setDownloader('file', new FileDownloader($this->io,
                $composer->getConfig(), $composer->getEventDispatcher(), $cache, $rfs));

        $composer->getPluginManager()->addPlugin(new FidoPlugin(__DIR__ . '/__tmp'));
        $update = new UpdateCommand();
        $update->setComposer($composer);
        $update->setIO($this->io);

        $update->run(new ArrayInput(array()), new BufferedOutput());
    }

    public function thenTheOutputShouldBe($output) {
        $this->spec->assertEquals($output, str_replace("\n", "", $this->file->makeRooted($this->io->getOutput())));
    }

    public function thenTheOutputShouldContain($string) {
        $this->spec->assertContains($string, $this->file->makeRooted($this->io->getOutput()));
    }

    public function then_ShouldBeExecuted($string) {
        foreach ($this->executor->commands as $i => $command) {
            $command = $this->file->makeRooted($command);
            $this->executor->commands[$i] = $command;
            if (strpos($command, $string) !== false) {
                return;
            }
        }
        $this->spec->fail("Could not find [$string] in " . print_r($this->executor->commands, true));
    }

} 