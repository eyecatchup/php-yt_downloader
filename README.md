# yt_downloader: Download videos from YouTube.

## Introduction

This class takes a YouTube URL or ID an downloads the original video to your computer.

## (Most simple) Example

For a more advanced example see example.php.

```php
<?php
    require('youtube-dl.class.php');
    try {
        // New instance.
        $mytube = new yt_downloader();

        /**
         *  Define the video to download.
         *  The set_youtube method takes either a YouTube Video-ID, 
         *  or any YouTube URL (class extracts id, if URL given).
         */
        $youtube = "http://www.youtube.com/watch?v=aahOEZKTCzU";
        $mytube->set_youtube($youtube);

        // Start the download.
        $mytube->do_download();
    } 
    catch (Exception $e) {
        die($e->getMessage());
    }
```
