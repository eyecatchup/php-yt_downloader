# yt_downloader - PHP class to download videos from YouTube and/or convert them to mp3 audio files.

## Introduction

This PHP class takes a YouTube URL (or YouTube Video-ID) and downloads the video to your computer.
Optionally, you can convert any YouTube video to an MP3 Audio file (requires ffmpeg to be installed!).

## UI Demo
For an example integration, try the demo:   
http://eyecatchup.github.com/php-yt_downloader/index.html?vid=http://www.youtube.com/watch?v=cMMpLEGlFWE

## Download

The latest stable version can be downloaded from the downloads tab, or using the following link:   
https://github.com/downloads/eyecatchup/php-yt_downloader/eyecatchup-php-yt_downloader-403c053.zip   
   
The UI demo code is available here:
https://github.com/eyecatchup/php-yt_downloader/tree/gh-pages

## Basic Usage

Usage is pretty straight forward:

```php
<?php
    require('youtube-dl.class.php');
    try {
        // Instantly download a YouTube video (using the default settings).
        new yt_downloader('http://www.youtube.com/watch?v=aahOEZKTCzU', TRUE);

        // Instantly download a YouTube video as MP3 (using the default settings).
        new yt_downloader('http://www.youtube.com/watch?v=aahOEZKTCzU', TRUE, 'audio');
    }
    catch (Exception $e) {
        die($e->getMessage());
    }
```

You can provide either a YouTube URL (as used in the example), or a Youtube Video-ID. The class will check whether the given  input value is a YouTube URL, or a YouTube Video-ID . If it's a URL, the ID will be extracted automatically. So, 

`new yt_downloader("http://www.youtube.com/watch?v=aahOEZKTCzU");` is identical to `new yt_downloader("https://www.youtube.com/watch?feature=related&v=aahOEZKTCzU");` is identical to `new yt_downloader("aahOEZKTCzU");`.

For more (advanced) examples see the example-*.php files.

## Configuration

Use the youtube-dl.config.php file to set your download preferences.

URL: https://github.com/eyecatchup/php-yt_downloader/      
License: http://eyecatchup.mit-license.org/     
(c) 2012, Stephan Schmitz <eyecatchup@gmail.com>   
