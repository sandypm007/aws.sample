<?php

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

require_once __DIR__ . '/AwsS3.php';

$filename = 'README.md';
$s3 = AwsS3::getInstance($_ENV['AWS_BUCKET']);
$exists = $s3->objectExists($filename);
echo ($exists ? 'Yes' : 'No') . PHP_EOL;
if ($exists) {
    echo $s3->objectUrl($filename) . PHP_EOL;
}
AwsS3::getInstance('development.univcell.com')->uploadFile($filename, __DIR__ . '/' . $filename);