<?php
namespace spec\watoki\fido;

use spec\watoki\fido\fixture\FidoFixture;
use spec\watoki\fido\fixture\FileFixture;
use watoki\scrut\Specification;

/**
 * @property FidoFixture fido <-
 * @property FileFixture file <-
 */
class FromRepositoryTest extends Specification {

    function testCloneRepository() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git"
                    }
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldBe(
                'Fido: Cloning https://example.com/some/repo.git ...' .
                'Fido: Done.');
        $this->file->thenThereShouldBeADirectory('assets/vendor');
        $this->fido->thenItShouldExecute('cd $root/assets/vendor && git clone https://example.com/some/repo.git');
    }

    function testUpdateRepository() {
        $this->file->givenTheDirectory('assets/vendor/repo');
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git"
                    }
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldBe(
                'Fido: Updating https://example.com/some/repo.git ...' .
                'Fido: Done.');
        $this->fido->thenItShouldExecute('cd $root/assets/vendor/repo && git pull origin master');
    }

    function testSourceAsKey() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "https://example.com/some/repo.git":{}
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldBe(
                'Fido: Cloning https://example.com/some/repo.git ...' .
                'Fido: Done.');
        $this->file->thenThereShouldBeADirectory('assets/vendor');
        $this->fido->thenItShouldExecute('cd $root/assets/vendor && git clone https://example.com/some/repo.git');
    }

} 