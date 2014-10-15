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
        $this->fido->thenTheOutputShouldContain('Cloning https://example.com/some/repo.git');
        $this->file->thenThereShouldBeADirectory('assets/vendor');
        $this->fido->thenItShouldExecute('cd $root/assets/vendor && git clone https://example.com/some/repo.git repo 2>&1');
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
        $this->fido->thenTheOutputShouldContain('Updating https://example.com/some/repo.git');
        $this->fido->thenItShouldExecute('cd $root/assets/vendor/repo && git pull origin master 2>&1 && cd ..');
    }

    function testSpecifyTag() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git",
                        "tag":"some_tag"
                    }
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldContain('Using tag some_tag');
        $this->fido->thenItShouldExecute('cd $root/assets/vendor && git clone https://example.com/some/repo.git repo 2>&1 && cd repo && git checkout some_tag 2>&1');
    }

    function testSpecifyTargetFolder() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git",
                        "target":"my/target"
                    }
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldContain('Cloning https://example.com/some/repo.git to $root/assets/vendor/my/target');
        $this->fido->thenItShouldExecute('cd $root/assets/vendor/my && git clone https://example.com/some/repo.git target 2>&1');
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
        $this->fido->thenTheOutputShouldContain('Cloning https://example.com/some/repo.git');
    }

    function testTagAsValue() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "https://example.com/some/repo.git":"some_tag"
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldContain('Cloning https://example.com/some/repo.git');
        $this->fido->thenTheOutputShouldContain('Using tag some_tag');
    }

} 