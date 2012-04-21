<?php
/**
 * yt_downloader
 * PHP class to get the file location of Youtube videos
 * and download the source file to a local path.
 *
 * @author      Stephan Schmitz <eyecatchup@gmail.com>
 * @updated     2012/04/21
 * @copyright	2012-present, Stephan Schmitz
 * @url         https://github.com/eyecatchup/php-yt_downloader/
 */

// Downloading HD Videos may take some time.
ini_set('max_execution_time', 360);
// Writing HD Videos to your disk, may need some extra resources.
ini_set('memory_limit', '64M');

// Include the config interface.
require('youtube-dl.config.php');
// Include helper functions for usort.
require('comparisonfunctions.usort.php');

/**
 *  yt_downloader Class
 */
class yt_downloader implements cnfg
{
    /**
     *  Class constructor method.
     *  @access  public
     *  @return  void
     */	
    public function __construct()
    {
        // Ensure the PHP extensions CURL and JSON are installed.
        if (!function_exists('curl_init')) {
            throw new Exception('Script requires the PHP CURL extension.');
            exit(0); }
        if (!function_exists('json_decode')) {
            throw new Exception('Script requires the PHP JSON extension.');
            exit(0); }

        // Required YouTube URLs.
        $this->YT_BASE_URL = "http://www.youtube.com/";
        $this->YT_INFO_URL = $this->YT_BASE_URL . "get_video_info?video_id=%s&el=embedded&ps=default&eurl=&hl=en_US";
        $this->YT_INFO_ALT = $this->YT_BASE_URL . "oembed?url=%s&format=json";
        $this->YT_THUMB_URL = "http://img.youtube.com/vi/%s/%s.jpg";
        $this->YT_THUMB_ALT = "http://i1.ytimg.com/vi/%s/%s.jpg";

        $this->CURL_UA = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko Firefox/11.0";

        // Set default parameters for this download instance.		
        self::set_defaults();
    }

    /**
     *  Set the YouTube Video that shall be downloaded.
     *  @access  public
     *  @return  void
     */	
    public function set_youtube($str)
    {
        /**
         *  Parse input string to determine if it's a Youtube URL,
         *  or an ID. If it's a URL, extract the ID from it.
         */
        $tmp_id = self::parse_yturl($str);
        $vid_id = ($tmp_id !== false) ? $tmp_id : $str;

        /**
         *  Check the public video info feed to check if we got a
         *  valid Youtube ID. If not, throw an exception and exit.
         */
        $url = sprintf($this->YT_BASE_URL . "watch?v=%s", $vid_id);
        $url = sprintf($this->YT_INFO_ALT, urlencode($url));    
        if(self::curl_httpstatus($url) !== 200) {
            throw new Exception("Invalid Youtube video ID: $vid_id");
            exit(); }		
        else { self::set_video_id($vid_id); }
    }

    /**
     *  Try to download the defined YouTube Video.
     *  @access  public
     *  @return  integer  Returns (int)0 if download succeded, or (int)1 if 
     *                    the video already exists on the download directory.
     */
    public function do_download()
    {
        /**
         *  If we have a valid Youtube Video Id, try to get the real location 
         *  and download the video. If not, throw an exception and exit.
         */
        $id = self::get_video_id();
        if($id === false) {
            throw new Exception("Missing video id. Use set_youtube() and try again.");
            exit(); 
        }
        else {
            /**
             *  Try to parse the YouTube Video-Info file to get the video URL map,
             *  that holds the locations on the YouTube media servers.
             */
            $v_info = self::get_yt_info();
            if(self::get_videodata($v_info) === true) 
            {
                $vids = self::get_url_map($v_info);

                /**
                 *  If extracting the URL map failed, throw an exception
                 *  and exit. Try to include the original YouTube error
                 *  - eg "forbidden by country"-message.
                 */
                if(!is_array($vids) || sizeof($vids) == 0) {
                    $err_msg = "";
                    if(strpos($v_info, "status=fail") !== false) {
                        preg_match_all('#reason=(.*?)$#si', $v_info, $err_matches);
                        if(isset($err_matches[1][0])) {
                            $err_msg = urldecode($err_matches[1][0]);
                            $err_msg = str_replace("Watch on YouTube", "", strip_tags($err_msg));
                            $err_msg = "Youtube error message: ". $err_msg; 
                        } 
                    }
                    throw new Exception("Grabbing original file location failed. $err_msg");
                    exit();
                }

                /**
                 *  Format video title and set download and file preferences.
                 */
                $title   = self::get_video_title();
                $quality = self::get_video_quality();
                $path    = self::get_downloads_dir();

                if($quality == 1) {
                    usort($vids, 'asc_by_quality');
                } else if($quality == 0) {
                    usort($vids, 'desc_by_quality');
                }

                $YT_Video_URL = $vids[0]["url"];
                $res = $vids[0]["type"];
                $ext = $vids[0]["ext"];	

                $videoTitle    = $title . "_-_" . $res ."_-_youtubeid-$id";
                $videoFilename = "$videoTitle.$ext";
                $thumbFilename = "$title.jpg";
                $video         = $path . $videoFilename;
                        
                self::set_video($videoFilename);

                /**
                 *  PHP doesn't cache information about non-existent files.
                 *  So, if you call file_exists() on a file that doesn't exist, 
                 *  it will return FALSE until you create the file.
                 *  So, if you create the file, it will return TRUE - even if 
                 *  you then delete the file !!! However unlink() clears the 
                 *  cache automatically. Nevertheless, since we don't know which
                 *  way the file may have been deleted (if it existed), we clear 
                 *  the file status cache to ensure a valid file_exists result.
                 */
                clearstatcache();

                /**
                 *  If the video does not already exist in the download directory,
                 *  try to download the video and the video preview image.
                 */
                if(!file_exists($video)) 
                {	
                    self::check_thumbs($id);
                    touch($video);
                    chmod($video, 0775);

                    $download = self::curl_get_file($YT_Video_URL, $video);
                    if($download === false) {
                        throw new Exception("Saving $videoFilename to $path failed.");
                        exit(); 
                    }
                    else {
                        $thumb = self::get_video_thumb();
                        if($thumb !== false) 
                        {
                            $thumbnail = $path . $thumbFilename;
                            self::curl_get_file($thumb, $thumbnail);
                            self::set_thumb($thumbFilename);
                            chmod($thumbnail, 0775); 
                        }
                        return 0;
                    }
                } 
                else {
                    self::set_thumb($thumbFilename);			  
                    return 1; 
                }
            }
        }
    }

    /**
     *  Get filestats for the downloaded video.
     *  @access  public
     *  @return  array   Returns an array containing formatted filestats.
     */	
    public function video_stats()
    {
        $file = self::get_video();
        $path = self::get_downloads_dir();

        clearstatcache();
        $filestats = stat($path . $file);
        if($filestats !== false) {
            return array(
                "size" => self::human_bytes($filestats["size"]),
                "created" => date ("d.m.Y H:i:s.", $filestats["ctime"]),
                "modified" => date ("d.m.Y H:i:s.", $filestats["mtime"]) 
            );
        } 
        else { return false; }		
    }
	
    private function set_defaults()
    {
        $this->video = false;
        $this->thumb = false;
        $this->videoID = false;
        $this->videoExt = false;
        $this->videoTitle = false;
        $this->videoThumb = false;
        $this->videoQuality = false;
        $this->videoThumbSize = false;
        $this->downloadsFolder = false;

        self::set_downloads_dir(cnfg::Download_Folder);
        self::set_thumb_size(cnfg::Default_Thumbsize);
        self::set_video_quality(cnfg::Default_Videoquality);
    }
	
    private function parse_yturl($url) 
    {
        $pattern = '#^(?:https?://)?';    # Optional URL scheme. Either http or https.
        $pattern .= '(?:www\.)?';         #  Optional www subdomain.
        $pattern .= '(?:';                #  Group host alternatives:
        $pattern .=   'youtu\.be/';       #    Either youtu.be,
        $pattern .=   '|youtube\.com';    #    or youtube.com
        $pattern .=   '(?:';              #    Group path alternatives:
        $pattern .=     '/embed/';        #      Either /embed/,
        $pattern .=     '|/v/';           #      or /v/,
        $pattern .=     '|/watch\?v=';    #      or /watch?v=,	
        $pattern .=     '|/watch\?.+&v='; #      or /watch?other_param&v=
        $pattern .=   ')';                #    End path alternatives.
        $pattern .= ')';                  #  End host alternatives.
        $pattern .= '([\w-]{11})';        # 11 characters (Length of Youtube video ids).
        $pattern .= '(?:.+)?$#x';         # Optional other ending URL parameters.
        preg_match($pattern, $url, $matches);
        return (isset($matches[1])) ? $matches[1] : false;
    }
	
    private function get_yt_info()
    {
        $url = sprintf($this->YT_INFO_URL, self::get_video_id());	
        return self::curl_get($url);
    }
	
    private function get_public_info()
    {
        $url = sprintf($this->YT_BASE_URL . "watch?v=%s", self::get_video_id());
        $url = sprintf($this->YT_INFO_ALT, urlencode($url));
        $info = json_decode(self::curl_get($url), true);

        if(is_array($info) && sizeof($info) > 0) {
            return array(
                "title" => $info["title"],
                "thumb" => $info["thumbnail_url"]
            );
        } 
        else { return false; }
    }
	
    private function get_videodata($str)
    {
        $yt_info = $str;
        $pb_info = self::get_public_info();
	
        if($pb_info !== false) {
            $htmlTitle = htmlentities(utf8_decode($pb_info["title"]));
            $videoTitle = self::canonicalize($htmlTitle);
        } 
        else {
            $videoTitle = self::formattedVideoTitle($yt_info);
        }
        
        if(is_string($videoTitle) && strlen($videoTitle) > 0) {
            self::set_video_title($videoTitle);
            return true;
        } 
        else { return false; }
    }
	
    private function get_url_map($data)
    {
        preg_match('/stream_map=(.[^&]*?)&/i',$data,$match);
        if(!isset($match[1])) {
            return false;
        }
        else {
            $fmt_url =  urldecode($match[1]);
            if(preg_match('/^(.*?)\\\\u0026/',$fmt_url,$match2)) {
                $fmt_url = $match2[1];
            }

            $urls = explode(',',$fmt_url);
            $tmp = array();

            foreach($urls as $url) {
                if(preg_match('/url=(.*?)&.*?itag=([0-9]+)/si',$url,$um))
                {
                    $u = urldecode($um[1]);
                    $tmp[$um[2]] = $u;
                }
            }

            $formats = array(
                '13' => array('3gp', '240p', '10'),
                '17' => array('3gp', '240p', '9'),
                '36' => array('3gp', '320p', '8'),
                '5'  => array('flv', '240p', '7'),
                '6'  => array('flv', '240p', '6'),
                '34' => array('flv', '320p', '5'),
                '35' => array('flv', '480p', '4'),
                '18' => array('mp4', '480p', '3'),
                '22' => array('mp4', '720p', '2'),
                '37' => array('mp4', '1080p', '1')
            );

            foreach ($formats as $format => $meta) {
                if (isset($tmp[$format])) {
                    $videos[] = array('pref' => $meta[2], 'ext' => $meta[0], 'type' => $meta[1], 'url' => $tmp[$format]);
                } 
            }
            return $videos;
        }
    }
	
    private function check_thumbs($id)
    {
        $thumbsize = self::get_thumb_size();
        $thumb_uri = sprintf($this->YT_THUMB_URL, $id, $thumbsize);

        if(self::curl_httpstatus($thumb_uri) == 200) {
            $th = $thumb_uri; 
        }
        else {
            $thumb_uri = sprintf($this->YT_THUMB_ALT, $id, $thumbsize);
            
            if(self::curl_httpstatus($thumb_uri) == 200) {
                $th = $thumb_uri; 
            }
            else { $th = false; }
        }
        self::set_video_thumb($th);	
    }

    private function formattedVideoTitle($str)
    {
        preg_match_all('#title=(.*?)$#si', urldecode($str), $matches);

        $title = explode("&", $matches[1][0]);
        $title = $title[0];
        $title = htmlentities(utf8_decode($title));
        
        return self::canonicalize($title);
    }

    private function canonicalize($str)
    {
        $str = trim($str); # Strip unnecessary characters from the beginning and the end of string.
        $str = str_replace("&quot;", "", $str); # Strip quotes.
        $str = self::strynonym($str); # Replace special character vowels by their equivalent ASCII letter.
        $str = preg_replace("/[[:blank:]]+/", "_", $str); # Replace all blanks by an underscore.
        $str = preg_replace('/[^\x9\xA\xD\x20-\x7F]/', '', $str); # Strip everything what is not valid ASCII.	
        $str = preg_replace('/[^\w\d_-]/si', '', $str); # Strip everything what is not a word, a number, "_", or "-".
        $str = str_replace('__', '_', $str); # Fix duplicated underscores.
        $str = str_replace('--', '-', $str); # Fix duplicated minus signs.
        if(substr($str, -1) == "_" OR substr($str, -1) == "-") {
            $str = substr($str, 0, -1); # Remove last character, if it's an underscore, or minus sign.
        }
        return trim($str);
    }

    /**
     *  Replaces common special entity codes for special character
     *  vowels by their equivalent ASCII letter.
     */
    private function strynonym($str)
    {
        $SpecialVowels = array(
            '&Agrave;'=>'A', '&agrave;'=>'a', '&Egrave;'=>'E', '&egrave;'=>'e', '&Igrave;'=>'I', '&igrave;'=>'i', '&Ograve;'=>'O', '&ograve;'=>'o', '&Ugrave;'=>'U', '&ugrave;'=>'u',
            '&Aacute;'=>'A', '&aacute;'=>'a', '&Eacute;'=>'E', '&eacute;'=>'e', '&Iacute;'=>'I', '&iacute;'=>'i', '&Oacute;'=>'O', '&oacute;'=>'o', '&Uacute;'=>'U', '&uacute;'=>'u', '&Yacute;'=>'Y', '&yacute;'=>'y',
            '&Acirc;'=>'A', '&acirc;'=>'a', '&Ecirc;'=>'E', '&ecirc;'=>'e', '&Icirc;'=>'I',  '&icirc;'=>'i', '&Ocirc;'=>'O', '&ocirc;'=>'o', '&Ucirc;'=>'U', '&ucirc;'=>'u',
            '&Atilde;'=>'A', '&atilde;'=>'a', '&Ntilde;'=>'N', '&ntilde;'=>'n', '&Otilde;'=>'O', '&otilde;'=>'o',
            '&Auml;'=>'Ae', '&auml;'=>'ae', '&Euml;'=>'E', '&euml;'=>'e', '&Iuml;'=>'I', '&iuml;'=>'i', '&Ouml;'=>'Oe', '&ouml;'=>'oe', '&Uuml;'=>'Ue', '&uuml;'=>'ue', '&Yuml;'=>'Y', '&yuml;'=>'y',
            '&Aring;'=>'A', '&aring;'=>'a', '&AElig;'=>'Ae', '&aelig;'=>'ae', '&Ccedil;'=>'C', '&ccedil;'=>'c', '&OElig;'=>'OE', '&oelig;'=>'oe', '&szlig;'=>'ss', '&Oslash;'=>'O', '&oslash;'=>'o'
        );
        return strtr($str, $SpecialVowels);
    }
	
    private function is_dldir($dir)
    {
        if(is_dir($dir) !== false) {
            return true;
        } 
        else {
            return (bool) ! mkdir($dir, 0755);
        }
    }
	
    private function curl_httpstatus($url)
    {
        $ch = curl_init($url);
	curl_setopt($ch, CURLOPT_USERAGENT, $this->CURL_UA);
	curl_setopt($ch, CURLOPT_REFERER, $this->YT_BASE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $str = curl_exec($ch);
        $int = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return intval($int);
    }

    private function curl_get($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->CURL_UA);
        curl_setopt($ch, CURLOPT_REFERER, $this->YT_BASE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $contents = curl_exec ($ch);
        curl_close ($ch);
        return $contents;
    }
	
    private function curl_get_file($remote_file, $local_file)
    {
        $ch = curl_init($remote_file);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->CURL_UA);
        curl_setopt($ch, CURLOPT_REFERER, $this->YT_BASE_URL);
        $fp = fopen($local_file, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec ($ch);
        curl_close ($ch);
        fclose($fp);
    }	

    public function get_video() {
        return $this->video; }
    private function set_video($video) {
        $this->video = $video; }

    public function get_thumb() {
        return $this->thumb; }
    private function set_thumb($img) {
        if(is_string($img)) {
            $this->thumb = $img;
        } else {
            throw new Exception("Invalid thumbnail given: $img"); }
    }
	
    public function get_video_id() {
        return $this->videoID; }
    public function set_video_id($id) {
        if(strlen($id) == 11) {
            $this->videoID = $id;
        } else {
            throw new Exception("$id is not a valid Youtube Video ID."); }
    }

    public function get_video_title() {
        return $this->videoTitle; }
    private function set_video_title($str) {
        if(is_string($str)) {
            $this->videoTitle = $str;
        } else {
            throw new Exception("Invalid title given: $str"); }
    }
	
    public function get_video_quality() {
        return $this->videoQuality; }
    public function set_video_quality($q) {
        if(in_array($q, array(0,1))) {
            $this->videoQuality = $q;
        } else {
            throw new Exception("Invalid video quality.."); }
    }
	
    public function get_video_thumb() {
        return $this->videoThumb; }
    private function set_video_thumb($img) {
        $this->videoThumb = $img; }

    public function get_thumb_size() {
        return $this->videoThumbSize; }
    public function set_thumb_size($s) {
        if($s == "s") {
            $this->videoThumbSize = "default"; } 
        else if($s == "l") {
            $this->videoThumbSize = "hqdefault"; } 
        else {
            throw new Exception("Invalid thumbnail size specified."); }
    }
	
    public function get_downloads_dir() {
        return $this->downloadsFolder; }
    public function set_downloads_dir($dir) {
        if(self::is_dldir($dir) !== false) {
            $this->downloadsFolder = $dir;
        } else {
            throw new Exception("Can neither find, nor create download folder: $dir"); }
    }
		
    public function human_bytes($bytes) 
    {
        $fsize = $bytes;
        switch ($bytes):
            case $bytes < 1024:
                $fsize = $bytes .' B'; break;
            case $bytes < 1048576:
                $fsize = round($bytes / 1024, 2) .' KiB'; break;
            case $bytes < 1073741824:
                $fsize = round($bytes / 1048576, 2) . ' MiB'; break;
            case $bytes < 1099511627776:
                $fsize = round($bytes / 1073741824, 2) . ' GiB'; break;
        endswitch;
        return $fsize;
    }
}
