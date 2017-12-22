<?php
if(!isset($_GET["vid"])) { exit("Nope."); }
else {
    //  ADJUST PATH !!!
    require('../youtube-dl.class.php');
    try
    {
        $mytube = new yt_downloader();
		
        //Fixing shorted yt url result in error
		$yt_url = $_GET["vid"];
		if(str_pos("youtu.be", $yt_url) !== false) {
			$yt_url = preg_replace('~^https?://youtu\.be/([a-z\d]+)$~i', 'http://www.youtube.com/watch?v=$1', $yt_url);
		}
		
        $mytube->set_youtube($yt_url);     # YouTube URL (or ID) of the video to download.
        $mytube->set_video_quality(1);          # Change default output video file quality.
        $mytube->set_thumb_size('l');           # Change default video preview image size.
        $mytube->set_ffmpegLogs_active(FALSE);

        $mediatype = isset($_GET["dl"]) && in_array($_GET["dl"], array("video", "audio")) ?
            $_GET["dl"] : $mytube->get_default_download();

        $download = $mytube->do_download($mediatype);

        if($download == 0 || $download == 1)
        {
            $file = ($mediatype == "audio") ? $mytube->get_audio() : $mytube->get_video();
            $path = $mytube->get_downloads_dir();
            print "Done! Here is your <a style='color: #fff;' href='download.php?file=". $path . $file ."' target='_blank'>download link</a>.";
        }
        else { print "Sorry, an error occured. Try again."; }
    }
    catch (Exception $e) {
        die($e->getMessage());
    }
}
