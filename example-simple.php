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
