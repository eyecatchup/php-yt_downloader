# yt_downloader: Download videos from YouTube.

## Introduction

This class takes a YouTube URL or ID an downloads the original video to your computer.

## (Most simple) Example

Usage is pretty straight forward: 

 - Create a new instance.
 - Use the `set_youtube` method to define the video to download.
 - Use the `do_download` method to download the YouTube video.

```php
<?php
    require('youtube-dl.class.php');
    try {
        // New download instance.
        $mytube = new yt_downloader();
        // Define the video to download.
        $mytube->set_youtube("http://www.youtube.com/watch?v=aahOEZKTCzU");
        // Download the video (and a preview image).
        $mytube->do_download();
    } 
    catch (Exception $e) {
        die($e->getMessage());
    }
```

The input string to the `set_youtube` method will be parsed and the class recognizes if it's a YouTube URL, or a YouTube Video-ID. 
If it's a URL, the ID will be extracted automatically. So, you can provide both: a YouTube Video-ID, or any YouTube URL.

For a more advanced example see example.php.

## Configuration

The following options can be set in the youtube-dl.config.php file.

```php
<?php
  interface cnfg
  {
      /**
       *  Set directory to save the downloads to.
       */
      const Download_Folder = 'videos/';
	
      /**
       *  Set video quality.
       *  Choose '1' to download videos in the best quality available, 
       *  or '0' for the lowest quality (,thus smallest file size).
       */
      const Default_Videoquality = 0;
	
      /**
       *  Set thumbnail size.
       *  Choose one of 'l' (480*360px), or 's' (120*90px).
       */
      const Default_Thumbsize = 'l';
  }
```

(c) 2012, Stephan Schmitz <eyecatchup@gmail.com>

