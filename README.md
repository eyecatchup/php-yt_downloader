# yt_downloader: PHP class to download videos from YouTube

## Introduction

This PHP class takes a YouTube URL (or YouTube Video-ID) and downloads the video to your computer.
Optionally, you can define the video quality, filetype and preview image dimensions.

## Basic Usage

Usage is pretty straight forward:

```php
<?php
    require('youtube-dl.class.php');
    try {
        // Create a new download instance.
        $mytube = new yt_downloader();
        // Instantly download a YouTube video (using default settings).
        $mytube->instant_download("http://www.youtube.com/watch?v=aahOEZKTCzU");
    } 
    catch (Exception $e) {
        die($e->getMessage());
    }
```

You can provide either a YouTube URL (as used in the example), or a Youtube Video-ID. The `instant_download` method will check whether the given  input value is a YouTube URL, or a YouTube Video-ID (as the `set_youtube` method does, too - see example.php). If it's a URL, the ID will be extracted automatically.
So, `$mytube->instant_download("http://www.youtube.com/watch?v=aahOEZKTCzU");` is identical to `$mytube->instant_download("https://www.youtube.com/watch?feature=related&v=aahOEZKTCzU");` is identical to `$mytube->instant_download("aahOEZKTCzU");`.

For a more advanced example see example.php.

## Configuration

Use the youtube-dl.config.php file to set your download preferences.

```php
<?php
  interface cnfg
  {
      // Relative path to the downloads directory.
      const Download_Folder = 'videos/';
	
      // Video quality: "1" (nummeric One) for best Quality,
      // or "0" (nummeric Null) for smallest filesize.
      const Default_Videoquality = 0;
	
      // Thumb size: "l" (small letter "L") for 480*360px, or "s" for 120*90px.
      const Default_Thumbsize = 'l';
  }
```

URL: https://github.com/eyecatchup/php-yt_downloader 
(c) 2012, Stephan Schmitz <eyecatchup@gmail.com>

