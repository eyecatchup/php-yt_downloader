<?php
session_cache_limiter('none');
set_time_limit(0); # HD videos may take some time!
ini_set('max_execution_time', 0);

forceDownload( $_GET["file"] );

/**
 * @author Stephan Schmitz <eyecatchup@gmail.com>
 */
function forceDownload($f)
{
    $filename = str_replace("videos/", "", $f);
    $file = "./videos/$filename";

    // required for IE, otherwise Content-Disposition may be ignored!
    if(ini_get('zlib.output_compression'))
    ini_set('zlib.output_compression', 'Off');

    // Figure out the MIME type
    $mime_types = array(
        ".mp3" => "audio/mpeg",        // Audio types
        ".ogg" => "audio/ogg",
        ".wav" => "audio/x-wav",
        ".wma" => "audio/x-ms-wma",
        ".mp4" => "video/mp4",        // Video types
        ".mpg" => "video/mpeg",
        ".mpeg" => "video/mpeg",
        ".mkv" => "video/divx",
        ".mov" => "video/quicktime",
        ".flv" => "video/x-flv",
        ".gif" => "image/gif",        // Image types
        ".png" => "image/png",
        ".jpeg"=> "image/jpg",
        ".jpg" => "image/jpg"
    );
    $mime = $mime_types[strrchr($filename, '.')];
    if($mime === NULL) {
          header ("HTTP/1.0 404 Not Found");
          exit(0);
    }

    clearstatcache();
    // if the file exists
    if(file_exists($file)) {
        // and the file is readable
        if(is_readable($file)){
            // get the file size
            $size=filesize("$file");
            // open the file for reading
            if($fp=@fopen("$file",'r')){
                // send the headers
                header('HTTP/1.1 200 OK');
                header("Content-type: $mime");
                header("Content-Length: $size");
                header("Content-Disposition: attachment; filename=\"$filename\"");
                header("Content-Transfer-Encoding: binary");
                // send the file content
                fpassthru($fp);
                // close the file
                fclose($fp);
                // close connection and quit
                header('Connection: close');
                exit;
            }
        }
    }
    else {
        header ("HTTP/1.0 404 Not Found");
        exit(0);
    }
}