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
