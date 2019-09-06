<?php
require 'vendor/autoload.php';

use MiniUpload\MyUpload as MyUpload;
use VK\Client\VKApiClient;

$miniUpload = new MyUpload();
$miniUpload->test();
$vk = new VKApiClient();