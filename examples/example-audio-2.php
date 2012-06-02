<?php
require('youtube-dl.class.php');
try {
    $mytube = new yt_downloader("http://www.youtube.com/watch?v=px17OLxdDMU", TRUE, "audio");

    $audio = $mytube->get_audio();
    $path_dl = $mytube->get_downloads_dir();

    clearstatcache();
    if($audio !== FALSE && file_exists($path_dl . $audio) !== FALSE)
    {
        print "<a href='". $path_dl . $audio ."' target='_blank'>Click, to open downloaded audio file.</a>";
    } else {
        print "Oups. Something went wrong.";
    }

    $log = $mytube->get_ffmpeg_Logfile();
    if($log !== FALSE) {
        print "<br><a href='" . $log . "' target='_blank'>Click, to view the Ffmpeg file.</a>";
    }
}
catch (Exception $e) {
    die($e->getMessage());
}
