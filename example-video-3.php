<?php
if(!isset($_GET["vid"])) { exit("Nope."); }
else {
    require('youtube-dl.class.php');
    try
    {
        $mytube = new yt_downloader();

        $mytube->set_youtube($_GET["vid"]);     # YouTube URL (or ID) of the video to download.
        $mytube->set_video_quality(1);          # Change default output video file quality.
        $mytube->set_thumb_size('s');           # Change default video preview image size.

        $download = $mytube->download_video();

        if($download == 0 || $download == 1)
        {
            $video = $mytube->get_video();

            if($download == 0) {
                print "<h2><code>$video</code><br>succesfully downloaded into your Downloads Folder.</h2>";
            }
            else if($download == 1) {
                print "<h2><code>$video</code><br>already exists in your your Downloads Folder.</h2>";
            }

            $filestats = $mytube->video_stats();
            if($filestats !== FALSE) {
                print "<h3>File statistics for <code>$video</code></h3>";
                print "Filesize: " . $filestats["size"] . "<br>";
                print "Created: " . $filestats["created"] . "<br>";
                print "Last modified: " . $filestats["modified"] . "<br>";
            }

            $path = $mytube->get_downloads_dir();
            print "<br><a href='". $path . $video ."' target='_blank'>Click, to open downloaded video file.</a>";

            $thumb = $mytube->get_thumb();
            clearstatcache();
            if($thumb !== FALSE && file_exists($path . $thumb)) {
                print "<hr><img src=\"". $path . $thumb ."\"><hr>";
            }
        }
    }
    catch (Exception $e) {
        die($e->getMessage());
    }
}
