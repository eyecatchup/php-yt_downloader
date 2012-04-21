# yt_downloader: Download videos from YouTube.

## Introduction

This class takes a YouTube URL or ID an downloads the original video to your computer.

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

        /**
         *  Start the download.
         *  Filenames are formatted as: 
         *  $YT-VideoTitle ."_-_". $Quality ."_-_youtubeid-". $YT-VideoID
         */
        $mytube->do_download();
    } 
    catch (Exception $e) {
        die($e->getMessage());
    }
```
