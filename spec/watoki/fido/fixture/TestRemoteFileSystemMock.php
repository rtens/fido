<?php


namespace spec\watoki\fido\fixture;


use Composer\Downloader\TransportException;
use Composer\Util\RemoteFilesystem;

class TestRemoteFileSystemMock extends RemoteFilesystem {

    /**
     * @param array $contentMap associative array of locations and content
     */
    public function __construct(array $contentMap)
    {
        $this->contentMap = $contentMap;
    }

    protected function get($originUrl, $fileUrl, $additionalOptions = array(), $fileName = null, $progress = true) {
        if (!empty($this->contentMap[$fileUrl])) {
            if ($fileName) {
                file_put_contents($fileName, $this->contentMap[$fileUrl]);
            }
            return $this->contentMap[$fileUrl];
        }

        throw new TransportException('The "'.$fileUrl.'" file could not be downloaded (NOT FOUND)', 404);
    }

}