<?php
namespace rtens\fido;

use Composer\Repository\PackageRepository;

class GitFetcher extends Fetcher {

    const TYPE = 'git';

    public function type() {
        return self::TYPE;
    }

    public function fetch($data, $name) {
        $reference = $this->determineGitReference($data);

        $this->composer->getRepositoryManager()->addRepository(new PackageRepository(array(
                'type' => 'package',
                'package' => array(
                        'name' => $name,
                        'version' => $reference ? : '1.0',
                        "source" => array(
                                "url" => $data['source'],
                                "type" => $this->type(),
                                "reference" => $reference ? : 'master'
                        )
                )
        )));

        return array($name => $this->determineTarget($data));
    }

    private function determineGitReference($data) {
        if (isset($data['value'])) {
            return $data['value'];
        } else if (isset($data['reference'])) {
            return $data['reference'];
        } else {
            return null;
        }
    }

    private function determineTarget($data) {
        if (isset($data['target'])) {
            return $data['target'];
        } else {
            return substr(basename($data['source']), 0, -4);
        }
    }
}