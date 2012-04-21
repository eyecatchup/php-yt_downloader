# yt_downloader: Download videos from YouTube.

## Introduction

This class takes a YouTube URL or ID an downloads the original video to your computer.

## (Most simple) Example

```php
<?php
    require('youtube-dl.class.php');
    try {
        // Can be a YouTube ID, or any YouTube URL.
        $youtube_url = "http://www.youtube.com/watch?v=aahOEZKTCzU";

        $mytube = new yt_downloader();
        $mytube->set_youtube($youtube_url);
        $mytube->do_download();
    } 
    catch (Exception $e) {
        die($e->getMessage());
    }
```
