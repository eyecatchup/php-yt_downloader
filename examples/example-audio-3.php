<?php
require('youtube-dl.class.php');
try
{
	$mytube = new yt_downloader("http://www.youtube.com/watch?v=px17OLxdDMU");

	$mytube->set_audio_format("wav");        # Change default audio output filetype.
	$mytube->set_ffmpegLogs_active(FALSE);   # Disable Ffmpeg process logging.

	$mytube->download_audio();
}
catch (Exception $e) {
	die($e->getMessage());
}
