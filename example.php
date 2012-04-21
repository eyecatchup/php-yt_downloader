<?php
/**
 *  Example Usage:
 *  http://yourhost.tld/example.php?vid=aahOEZKTCzU
 *  http://yourhost.tld/example.php?vid=http://www.youtube.com/watch?v=aahOEZKTCzU
 *  http://yourhost.tld/example.php?vid=https://www.youtube.com/watch?v=aahOEZKTCzU&feature=related
 */
if(!isset($_GET["vid"])) { exit("Nope."); }
else {
    require('youtube-dl.class.php');
    try 
    {
        $mytube = new yt_downloader();
        $mytube->set_youtube($_GET["vid"]); # Youtube URL, or Youtube video id.

        if(isset($_GET["ext"])) {
            $mytube->set_video_format($_GET["ext"]);
        }		

        $download = $mytube->do_download();
        
        if($download == 0 OR $download == 1) 
        {
            $video = $mytube->get_video();
			
            if($download == 0) {
                print "<h2><code>$video</code><br>succesfully downloaded into your Downloads Folder.</h2>"; 
            }
            else if($download == 1) {
                print "<h2><code>$video</code><br>already exists in your your Downloads Folder.</h2>"; 
            }

            $filestats = $mytube->video_stats();
            
            if($filestats !== false) {
                print "<h3>File statistics for <code>$video</code></h3>";
                print "Filesize: " . $filestats["size"] . "<br>";
                print "Created: " . $filestats["created"] . "<br>";
                print "Last modified: " . $filestats["modified"] . "<br>";
            }
			
            $path = $mytube->get_downloads_dir();

            print "<br><a href='". $path . $video ."' target='_blank'>Click, to open downloaded video.</a>";
			
            $thumb = $mytube->get_thumb();

            clearstatcache();
            if(file_exists($path . $thumb)) {
                print "<hr><img src=\"". $path . $thumb ."\"><hr>"; }		
            }
    } 
    catch (Exception $e) {
        die($e->getMessage());
    }
}
