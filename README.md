# TIKTOK DOWNLOADER VIDEO WITHOUT WATERMARK

PHP package Download TikTok video without watermark

## Install

```bash
$ composer require neihnat/tiktok-downloader
```

## Usage

1. Get data
```php
<?php

use NeihNat\TiktokDownloader\TiktokVideo;

$tiktokVideo = new TiktokVideo();
$cookie = 'tiktok-cookie-example.txt' // Replace your cookies here
$response = $tiktokVideo->setCookie($cookie) // Note: accepts strings or files
            ->setUrl($url) // Ex: https://www.tiktok.com/@neihnat/video/7321351732833897730
            ->getData();
return $response->data;

// Response
{#2055 ▼
  +"type": "video"
  +"video": "https://v16-webapp-prime.tiktok.com/video/tos/useast2a/tos-useast2a-ve-0068-euttp/oYiLPSkYhQaEBy0TL1ADZKtZT3bEwIBwIHi9s/?a=1988&ch=0&cr=3&dr=0&lr=unwatermarked& ▶"
  +"audio": "https://v16-webapp-prime.tiktok.com/video/tos/useast2a/tos-useast2a-v-2370-euttp/o8DPQtftgytElFCUoJ9BCjPwEiYDlQqQJnsQBf/?a=1988&ch=0&cr=0&dr=0&er=0&lr=default&c ▶"
  +"thumb": "https://p16-sign-useast2a.tiktokcdn.com/tos-useast2a-avt-0068-euttp/9ecb371afe13b6fac2c0d83403270d86~c5_100x100.jpeg?lk3s=a5d48078&x-expires=1705035600&x-signat ▶"
  +"title": "Amazing TOP"
  +"description": "Please Watch until the End!  🙏 #cleaning #drain #satisfying #rivercleaning #draincleaning #rec "
  +"images": null
}
```
2. Get HTML
```php
<?php
// Result will be an html string that you can use to get the elements you want
use NeihNat\TiktokDownloader\TiktokVideo;

$tiktokVideo = new TiktokVideo();
$cookie = 'tiktok-cookie-example.txt' // Replace your cookies here
$response = $tiktokVideo->setCookie($cookie)
            ->setUrl($url)
            ->getOriginData();
return $response;
```
3. Download Tiktok video, audio, image
```php
<?php

use NeihNat\TiktokDownloader\TiktokVideo;

$tiktokVideo = new TiktokVideo();
        $tiktokVideo->setCookie($cookie)
            ->setUrl($url)
            ->getData();
// Download video
$videoLink = $tiktokVideo->data->video; // Get video link
$fileName = 'neihnat-video-downloader'; // Name of the video after downloading
$type = 'video' // Accept type: video, audio, image
$download = $tiktokVideo->download($videoLink, $fileName, $type);
return $download;
```
## Features
 
- Download Tiktok video without watermark
- Download Tiktok image 
- Download Tiktok audio

## Notes.
> **NOTE:** You should use the "You need using extension "Get cookies.txt LOCALLY" extension to download cookies.

