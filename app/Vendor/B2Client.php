<?php

namespace App\Vendor;

use BackblazeB2\Client;
use BackblazeB2\Exceptions\B2Exception;
use BackblazeB2\File;
use GuzzleHttp\Exception\GuzzleException;

class B2Client extends Client
{

    protected string $masterAccountId;
    protected string $applicationKeyId;

    public function __construct($accountId, $applicationKey, array $options = [])
    {
        parent::__construct($accountId, $applicationKey, $options);

        $this->masterAccountId = substr($accountId, 3, 12);
        $this->applicationKeyId = $accountId;
    }

    /**
     * Overriding b2's default authorize behaviour because it doesn't work with app keys...
     * why?
     */
    protected function authorizeAccount()
    {
        $this->accountId = $this->applicationKeyId;
        parent::authorizeAccount();
        $this->accountId = $this->masterAccountId;
    }

    /**
     * Override of parent::listFiles() because of the following reasons:
     * - options suck, can't mod maxFileCount
     * - file constructor is half ass
     */
    public function customListFiles(array $options)
    {
        // if FileName is set, we only attempt to retrieve information about that single file.
        $fileName = !empty($options['FileName']) ? $options['FileName'] : null;

        $nextFileName = null;
        // ORIGINAL
        // $maxFileCount = 1000;
        $maxFileCount = $options['MaxKeys'] ?? 1000;

        $prefix = isset($options['Prefix']) ? $options['Prefix'] : '';
        $delimiter = isset($options['Delimiter']) ? $options['Delimiter'] : null;

        $files = [];

        if (!isset($options['BucketId']) && isset($options['BucketName'])) {
            $options['BucketId'] = $this->getBucketIdFromName($options['BucketName']);
        }

        if ($fileName) {
            $nextFileName = $fileName;
            $maxFileCount = 1;
        }

        $this->authorizeAccount();

        // B2 returns, at most, 1000 files per "page". Loop through the pages and compile an array of File objects.
        while (true) {
            $response = $this->sendAuthorizedRequest('POST', 'b2_list_file_names', [
                'bucketId'      => $options['BucketId'],
                'startFileName' => $nextFileName,
                'maxFileCount'  => $maxFileCount,
                'prefix'        => $prefix,
                'delimiter'     => $delimiter,
            ]);

            foreach ($response['files'] as $file) {
                // if we have a file name set, only retrieve information if the file name matches
                if (!$fileName || ($fileName === $file['fileName'])) {
                    // ORIGINAL
                    // $files[] = new File($file['fileId'], $file['fileName'], null, $file['size']);
                    $files[] = new File(
                        $file['fileId'],
                        $file['fileName'],
                        $file['contentSha1'] ?? null,
                        $file['contentLength'] ?? null,
                        $file['contentType'] ?? null,
                        $file['fileInfo'] ?? null,
                        $file['bucketId'] ?? null,
                        $file['action'] ?? null,
                        $file['uploadTimestamp'] ?? null,
                    );
                    dd($files);
                }
            }

            if ($fileName || $response['nextFileName'] === null) {
                // We've got all the files - break out of loop.
                break;
            }

            $nextFileName = $response['nextFileName'];
        }

        return $files;
    }

}
