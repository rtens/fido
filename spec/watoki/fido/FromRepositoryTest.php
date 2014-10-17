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
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeADirectory('assets/vendor');
        $this->fido->then_ShouldBeExecuted("git clone --no-checkout 'https://example.com/some/repo.git' 'vendor/fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632'");
        $this->fido->then_ShouldBeExecuted("git checkout 'master'");

        $this->fido->thenTheOutputShouldContain('Installing fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 (1.0)');
        $this->fido->thenTheOutputShouldContain('Cloning master');
    }

    function testSpecifyTag() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git",
                        "reference":"v1.1"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->fido->thenTheOutputShouldContain('Cloning v1.1');
    }

    function testUpdateRepository() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->file->givenTheDirectory('vendor/fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632/.git');
        $this->fido->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git",
                        "reference": "1.1"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->fido->then_ShouldBeExecuted('git fetch composer');
        $this->fido->then_ShouldBeExecuted('git fetch --tags composer');
        $this->fido->then_ShouldBeExecuted("git checkout '1.1'");

        $this->fido->thenTheOutputShouldContain('Updating fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 (1.0 => 1.1)');
        $this->fido->thenTheOutputShouldContain('Checking out 1.1');
    }

    function testSpecifyTargetFolder() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git",
                        "target":"my/target"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->fido->thenTheOutputShouldContain('vendor/fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 -> assets/vendor/my/target');
    }

    function testSourceAsKey() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "https://example.com/some/repo.git":{}
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->fido->thenTheOutputShouldContain('Installing fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 (1.0)');
    }

    function testTagAsValue() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "https://example.com/some/repo.git":"v1.3"
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->fido->thenTheOutputShouldContain('Cloning v1.3');
    }

    function testSpecifyType() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo",
                        "type":"git"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->fido->thenTheOutputShouldContain('Installing fido/repo-85c6fea5a41a6e5852f7a986453fee60 (1.0)');
    }

    function testInRequire() {
        $this->fido->givenTheComposerJson('{
            "require": {
                "fido-fetch:https://example.com/some/repo.git":"v1.3"
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->fido->thenTheOutputShouldContain('Installing fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 (v1.3)');
    }

} 