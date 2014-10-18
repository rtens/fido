<?php
namespace spec\watoki\fido;

use spec\watoki\fido\fixture\ComposerFixture;
use spec\watoki\fido\fixture\FileFixture;
use watoki\scrut\Specification;

/**
 * @property ComposerFixture $fix <-
 * @property FileFixture file <-
 */
class FromRepositoryTest extends Specification {

    function testCloneRepository() {
        $this->fix->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeADirectory('assets/vendor');
        $this->fix->then_ShouldBeExecuted("git clone --no-checkout 'https://example.com/some/repo.git' 'vendor/fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632'");
        $this->fix->then_ShouldBeExecuted("git checkout 'master'");

        $this->fix->thenTheOutputShouldContain('Installing fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 (1.0)');
        $this->fix->thenTheOutputShouldContain('Cloning master');
    }

    function testSpecifyTag() {
        $this->fix->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git",
                        "reference":"v1.1"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->fix->thenTheOutputShouldContain('Cloning v1.1');
    }

    function testUpdateRepository() {
        $this->fix->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->givenTheDirectory('vendor/fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632/.git');
        $this->fix->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git",
                        "reference": "1.1"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->fix->then_ShouldBeExecuted('git fetch composer');
        $this->fix->then_ShouldBeExecuted('git fetch --tags composer');
        $this->fix->then_ShouldBeExecuted("git checkout '1.1'");

        $this->fix->thenTheOutputShouldContain('Updating fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 (1.0 => 1.1)');
        $this->fix->thenTheOutputShouldContain('Checking out 1.1');
    }

    function testSpecifyTargetFolder() {
        $this->fix->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo.git",
                        "target":"my/target"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->fix->thenTheOutputShouldContain('vendor/fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 -> assets/vendor/my/target');
    }

    function testSourceAsKey() {
        $this->fix->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "https://example.com/some/repo.git":{}
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->fix->thenTheOutputShouldContain('Installing fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 (1.0)');
    }

    function testTagAsValue() {
        $this->fix->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "https://example.com/some/repo.git":"v1.3"
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->fix->thenTheOutputShouldContain('Cloning v1.3');
    }

    function testSpecifyType() {
        $this->fix->givenTheComposerJson('{
            "extra":{
                "fido-fetch": {
                    "some repo": {
                        "source":"https://example.com/some/repo",
                        "type":"git"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->fix->thenTheOutputShouldContain('Installing fido/repo-85c6fea5a41a6e5852f7a986453fee60 (1.0)');
    }

    function testInRequire() {
        $this->fix->givenTheComposerJson('{
            "require": {
                "fido-fetch:https://example.com/some/repo.git":"v1.3"
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->fix->thenTheOutputShouldContain('Installing fido/repo_git-7cd3d7da4878bedbcfde6f9a52615632 (v1.3)');
    }

} 