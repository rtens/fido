<?php
namespace watoki\fido;

use Composer\Repository\PackageRepository;

class GitFetcher extends Fetcher {

    const TYPE = 'git';

    public function type() {
        return self::TYPE;
    }

    public function fetch($fetch, $source, $name) {
        $reference = $this->determineGitReference($fetch);
        $target = $this->determineGitTarget($fetch, $source);
        $this->plugin->targets[$name] = $target;

        $this->plugin->composer->getRepositoryManager()->addRepository(new PackageRepository(array(
                'type' => 'package',
                'package' => array(
                        'name' => $name,
                        'version' => $reference ? : '1.0',
                        "source" => array(
                                "url" => $source,
                                "type" => $this->type(),
                                "reference" => $reference ? : 'master'
                        )
                )
        )));
    }

    private function determineGitReference($fetch) {
        if (is_string($fetch)) {
            return $fetch;
        } else if (isset($fetch['reference'])) {
            return $fetch['reference'];
        } else {
            return null;
        }
    }

    private function determineGitTarget($fetch, $source) {
        if (isset($fetch['target'])) {
            return $fetch['target'];
        } else {
            return substr(basename($source), 0, -4);
        }
    }
}