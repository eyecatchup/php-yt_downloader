<?php
require('youtube-dl.class.php');
try {
    new yt_downloader("http://www.youtube.com/watch?v=px17OLxdDMU", TRUE);
}
catch (Exception $e) {
    die($e->getMessage());
}
