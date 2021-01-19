<?php

require_once __DIR__ . '/vendor/autoload.php';

use Aws\Credentials\Credentials;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class AwsS3
{
    /** @var S3Client $s3Client */
    private $s3Client;

    /** @var string $bucket */
    private $bucket;

    /** @var AwsS3[] $instances */
    private static $instances = [];

    /**
     * @param string $bucket
     * @return AwsS3
     * @throws Exception In case of non existent bucket.
     */
    public static function getInstance(string $bucket)
    {
        if (!isset(self::$instances[$bucket])) {
            $self = self::$instances[$bucket] = new static;

            $self->bucket = $bucket;
            $self->s3Client = new S3Client([
                'region' => $_ENV['AWS_REGION'],
                'version' => $_ENV['AWS_VERSION'],
                'credentials' => new Credentials($_ENV['AWS_KEY'], $_ENV['AWS_SECRET']),
            ]);

            return $self->loadBucket($bucket);
        }

        return self::$instances[$bucket];
    }

    /**
     * Fluent bucket loader
     *
     * @param string $bucket
     * @return $this
     * @throws Exception In case of non existent bucket.
     */
    public function loadBucket(string $bucket)
    {
        if (!$this->s3Client->doesBucketExist($bucket)) {
            throw new Exception("Bucket \"{$bucket}\" does not exists!");
        }

        $this->bucket = $bucket;
        return $this;
    }

    /**
     * Checks if object exists un current loaded bucket
     *
     * @param string $path
     * @return boolean
     */
    public function objectExists(string $path)
    {
        return $this->s3Client->doesObjectExist($this->bucket, $path);
    }

    /**
     * Gets object url if object exists, null otherwise
     *
     * @param string $path
     * @return null|string
     * @throws Exception In case of non existent bucket.
     */
    public function objectUrl(string $path)
    {
        if ($this->s3Client->doesObjectExist($this->bucket, $path)) {
            return $this->s3Client->getObjectUrl($this->bucket, $path);
        }
        return null;
    }

    /**
     * Gets object url if object exists, null otherwise
     *
     * @param string $file_name
     * @param string $file_path
     * @return \Aws\Result
     */
    public function uploadFile(string $file_name, string $file_path)
    {
        return $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $file_name,
            'SourceFile' => $file_path,
        ]);
    }

    /**
     * Removes an existing object from the bucket.
     *
     * @param string $file_name
     * @return \Aws\Result
     */
    public function removeFile(string $file_name)
    {
        return $this->s3Client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $file_name
        ]);
    }

    /**
     * @param string $bucket
     * @param string $name
     * @return void
     */
    public static function tryRemoveObject(string $bucket, string $name)
    {
        try {
            $aws = AwsS3::getInstance($bucket);
            echo 'Attempting to delete "' . $name . '".';
            if ($aws->objectExists($name)) {
                $aws->removeFile($name);
            }
        } catch (S3Exception $e) {
            echo 'Failed to delete "' . $name . '".', ['ex' => $e->getAwsErrorMessage()];
        } catch (Exception $e) {
            echo 'Failed to delete "' . $name . '".', ['ex' => $e->getMessage()];
        }
    }
}
