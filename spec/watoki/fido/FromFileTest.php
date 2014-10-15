<?php
namespace spec\watoki\fido;

use Composer\Composer;
use Composer\Config;
use Composer\IO\BufferIO;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use spec\watoki\fido\fixture\FidoFixture;
use spec\watoki\fido\fixture\FileFixture;
use spec\watoki\fido\fixture\TestExecutorStub;
use watoki\fido\FidoPlugin;
use watoki\scrut\Specification;

/**
 * @property FileFixture file <-
 * @property FidoFixture fido <-
 */
class FromFileTest extends Specification {

    function testJustFileName() {
        $this->file->givenTheRemoteFile_Containing("http://example.com/some/file.txt", "Got me");
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "http://example.com/some/file.txt":{}
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldBe(
                'Fido: Downloading $root/some/file.txt ...' .
                'Fido: Done.');
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");
    }

    #############################################################################################

} 