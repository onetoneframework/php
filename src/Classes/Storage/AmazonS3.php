<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Storage;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Clover\Enumeration\{AWSACLFlag, AWSRegion};

class AmazonS3
{

    private array $credentials = [];

    private S3Client $client;

    public function __construct(AWSRegion $region = AWSRegion::US_EAST_1, string $version = 'latest', string $signature = 'v4', string $key, string $secret)
    {
        $this->credentials = [
            'key' => $key,
            'secret' => $secret
        ];

        $this->client = S3Client::factory([
            'region' => $region,
            'version' => $version,
            'signature' => $signature,
            'credentials' => $this->credentials,
            'use_path_style_endpoint' => true
        ]);
    }

    public function deleteBucketCors($bucket)
    {
        try {
            $result = $this->client->deleteBucketCors([
                'Bucket' => $bucket,
            ]);
        } catch (AwsException $exception) {
            throw $exception;
        }

        return $result;
    }

    public function deleteBucketLifecycle($bucket)
    {
        try {
            $result = $this->client->deleteBucketLifecycle([
                'Bucket' => $bucket,
            ]);
        } catch (AwsException $exception) {
            throw $exception;
        }

        return $result;
    }

    public function getObjectTorrent(string $bucket, string $key)
    {
        $result = $this->client->getObjectTorrent([
            'Bucket' => $bucket,
            'Key' => $key,
            'RequestPayer' => 'string',
        ]);

        return $result;
    }

    public function copyObject(string $bucket, string $source, string $key)
    {
        try {
            $this->client->copyObject([
                'Bucket' => $bucket,
                'CopySource' => $source,
                'Key' => $key,
            ]);
        } catch (\Exception $exception) {
            throw $exception;
        }

        return true;
    }

    public function headBucket(string $bucket)
    {
        $result = $this->client->headBucket([
            'Bucket' => $bucket,
        ]);

        return $result;
    }

    public function listObjectsV2(string $bucket)
    {
        return $this->client->listObjectsV2([
            'Bucket' => $bucket,
        ]);
    }

    public function deleteObjects(string $bucket, array $objects)
    {
        $this->client->deleteObjects([
            'Bucket' => $bucket,
            'Delete' => [
                'Objects' => $objects,
            ],
        ]);
    }

    public function deleteObject(string $bucket, string $key)
    {
        $this->client->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key
        ]);
    }

    public function listObjects(string $bucket)
    {
        $result = $this->client->listObjects(['Bucket' => $bucket]);
        $objects = $result['Contents'] ?? [];

        return $objects;
    }

    public function deleteBucket(string $bucket)
    {
        $this->client->deleteBucket(['Bucket' => $bucket]);
    }

    public function createBucket(string $bucket)
    {
        try {
            $result = $this->client->createBucket(['Bucket' => $bucket]);
        } catch (AwsException $exception) {
            throw $exception;
        }

        return $result;
    }

    public function listBuckets()
    {
        $result = $this->client->listBuckets();
        $buckets = $result['Buckets'] ?? [];

        return $buckets;
    }

    public function changeObjectAcl(string $bucket, string $key, AWSACLFlag $acl = AWSACLFlag::ACL_PUBLIC_READ)
    {
        $this->client->putObjectAcl([
            'Bucket' => $bucket,
            'Key' => $key,
            'ACL' => $acl
        ]);
    }

    public function getObjectUrl(string $bucket, string $key)
    {
        $url = $this->client->getObjectUrl($bucket, $key);

        return $url;
    }

    public function getSignedObjectUrl(string $bucket, string $key)
    {
        $command = $this->client->getCommand('GetObject', ['Bucket' => $bucket, 'Key' => $key]);
        $request = $this->client->createPresignedRequest($command, '+1 hour');

        return $request->getUri();
    }

    public function getObject(string $bucket, string $key)
    {
        $object = $this->client->getObject(['Bucket' => $bucket, 'Key' => $key]);

        return $object['Body']->getContents();
    }

    public function putObject(string $bucket, string $key, string $body, AWSACLFlag $acl = AWSACLFlag::ACL_PUBLIC_READ)
    {
        try {
            $this->client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => $body,
                'ACL' => $acl
            ]);
        } catch (\Exception $ex) {
            throw $ex;
        }

        return true;
    }

    public function putObjectFromFile(string $bucket, string $key, string $filePath, AWSACLFlag $acl = AWSACLFlag::ACL_PUBLIC_READ)
    {
        $fileHandler = fopen($filePath, 'r');

        $this->putObject(
            $bucket,
            $key,
            $fileHandler,
            $acl
        );
    }

}