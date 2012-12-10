<?php
/** PHP Class yt_downloader
 *  ================================================================================
 *  PHP class to retreive the file location of Youtube videos, download the video
 *  files to a local path and/or convert the videos into mp3 audio files.
 *  ================================================================================
 *  @category
 *  @package     yt_downloader
 *  @version     CVS: $Id: youtube-dl.class.php,v1.0.2 2012/12/10 01:49:01 ssc Exp $
 *  @author      Stephan Schmitz <eyecatchup@gmail.com>
 *  @copyright   2012 - present, Stephan Schmitz
 *  @license     http://eyecatchup.mit-license.org
 *  @link        https://github.com/eyecatchup/php-yt_downloader/
 *  ================================================================================
 *  LICENSE: Permission is hereby granted, free of charge, to any person obtaining
 *  a copy of this software and associated documentation files (the "Software"),
 *  to deal in the Software without restriction, including without limitation the
 *  rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *    The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY
 *  WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *  ================================================================================
 */

// Ensure the PHP extensions CURL and JSON are installed.
if (!function_exists('curl_init')) {
    throw new Exception('Script requires the PHP CURL extension.');
    exit(0);
}
if (!function_exists('json_decode')) {
    throw new Exception('Script requires the PHP JSON extension.');
    exit(0);
}

// Downloading HD Videos may take some time.
ini_set('max_execution_time', 0);
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
    public function __construct($str=NULL, $instant=FALSE, $out=NULL)
    {
        // Required YouTube URLs.
        $this->YT_BASE_URL = "http://www.youtube.com/";
        $this->YT_INFO_URL = $this->YT_BASE_URL . "get_video_info?video_id=%s&el=embedded&ps=default&eurl=&hl=en_US";
        $this->YT_INFO_ALT = $this->YT_BASE_URL . "oembed?url=%s&format=json";
        $this->YT_THUMB_URL = "http://img.youtube.com/vi/%s/%s.jpg";
        $this->YT_THUMB_ALT = "http://i1.ytimg.com/vi/%s/%s.jpg";

        $this->CURL_UA = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko Firefox/11.0";

        // Set default parameters for the download instance.
        $this->audio = FALSE;           # Formatted output filename(.ext) of the object's audio output.
        $this->video = FALSE;           # Formatted output filename(.ext) of the object's video output.
        $this->thumb = FALSE;           # Formatted output filename(.ext) of the object's preview image.
        $this->videoID = FALSE;         # YouTube video id.
        $this->videoExt = FALSE;        # Filetype of the object's video ouput file.
        $this->videoTitle = FALSE;      # Formatted output filename by original YouTube video title /wo ext.
        $this->videoThumb = FALSE;      # YouTube URL of the object's preview image.
        $this->videoQuality = FALSE;    # Whether downloading videos at the best quality available.
        $this->audioQuality = FALSE;    # Default audio output sample rate (in kbits).
        $this->audioFormat = FALSE;     # Default audio output filetype.
        $this->videoThumbSize = FALSE;  # Whether default thumbnail size is 120*90, or 480*360 px.
        $this->defaultDownload = FALSE; # Whether downloading video, or audio is default.
        $this->downloadThumbs = TRUE;   # Whether to download thumnails for downloads (Boolean TRUE/FALSE).
        $this->downloadsDir = FALSE;    # Relative path to the directory to save downloads to.
        $this->downloadsArray = FALSE;  # Array, containing the YouTube URLs of all videos available.
        $this->FfmpegLogsDir = FALSE;   # Absolute path to the directory to save ffmpeg logs to.
        $this->ffmpegLogfile = FALSE;   # Absolute path to the directory to save ffmpeg logs to.
        $this->FfmpegLogsActive = TRUE; # Whether to log ffmpeg processes (Boolean TRUE/FALSE).

        self::set_downloads_dir(cnfg::Download_Folder);
        self::set_ffmpegLogs_dir(cnfg::Ffmpeg_LogsDir);
        self::set_ffmpegLogs_active(cnfg::Ffmpeg_LogsActive);
        self::set_default_download(cnfg::Default_Download);
        self::set_download_thumbnail(cnfg::Download_Thumbnail);
        self::set_thumb_size(cnfg::Default_Thumbsize);
        self::set_video_quality(cnfg::Default_Videoquality);
        self::set_audio_quality(cnfg::Default_Audioquality);
        self::set_audio_format(cnfg::Default_Audioformat);

        // Optional constructor argument #1 will be used as YouTube Video-ID.
        if($str != NULL) {
            self::set_youtube($str);
            // Optional constructor argument #2 will start an instant download.
            if($instant === TRUE) {
                // Optional constructor argument #3 defines the media type to be saved.
                self::do_download($out);
            }
        }
    }

    public function do_download($out)
    {
        $action = ($out == "audio" || $out == "video") ? $out : self::get_default_download();
        return ($action == "audio" ) ? self::download_audio() : self::download_video();
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
        $vid_id = ($tmp_id !== FALSE) ? $tmp_id : $str;

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
     *  Get the direct links to the YouTube Video.
     *  @access  public
     *  @return  integer  Returns (int)0 if download succeded, or (int)1 if
     *                    the video already exists on the download directory.
     */
    public function get_downloads()
    {
        /**
         *  If we have a valid Youtube Video Id, try to get the real location
         *  and download the video. If not, throw an exception and exit.
         */
        $id = self::get_video_id();
        if($id === FALSE) {
            throw new Exception("Missing video id. Use set_youtube() and try again.");
            exit();
        }
        else {
            /**
             *  Try to parse the YouTube Video-Info file to get the video URL map,
             *  that holds the locations on the YouTube media servers.
             */
            $v_info = self::get_yt_info();
            if(self::get_videodata($v_info) === TRUE)
            {
                $vids = self::get_url_map($v_info);
                /**
                 *  If extracting the URL map failed, throw an exception
                 *  and exit. Try to include the original YouTube error
                 *  - eg "forbidden by country"-message.
                 */
                if(!is_array($vids) || sizeof($vids) == 0) {
                    $err_msg = "";
                    if(strpos($v_info, "status=fail") !== FALSE) {
                        preg_match_all('#reason=(.*?)$#si', $v_info, $err_matches);
                        if(isset($err_matches[1][0])) {
                            $err_msg = urldecode($err_matches[1][0]);
                            $err_msg = str_replace("Watch on YouTube", "", strip_tags($err_msg));
                            $err_msg = "Youtube error message: ". $err_msg;
                        }
                    }
                    return $err_msg;
                }
                else {
                    $quality = self::get_video_quality();
                    if($quality == 1) {
                        usort($vids, 'asc_by_quality');
                    } else if($quality == 0) {
                        usort($vids, 'desc_by_quality');
                    }
                    self::set_yt_url_map($vids);
                    return $vids;
                }
            }
        }
    }

    /**
     *  Try to download the defined YouTube Video.
     *  @access  public
     *  @return  integer  Returns (int)0 if download succeded, or (int)1 if
     *                    the video already exists on the download directory.
     */
    public function download_video($c=NULL)
    {
        $c = ($c !== NULL) ? $c : 0;
        /**
         *  If we have a valid Youtube Video Id, try to get the real location
         *  and download the video. If not, throw an exception and exit.
         */
        $id = self::get_video_id();
        if($id === FALSE) {
            throw new Exception("Missing video id. Use set_youtube() and try again.");
            exit();
        }
        else {
            $yt_url_map = self::get_yt_url_map();
            if($yt_url_map === FALSE) {
                $vids = self::get_downloads();
                self::set_yt_url_map($vids);
            } else {
                $vids = $yt_url_map;
            }
            if(!is_array($vids)) {
                throw new Exception("Grabbing original file location(s) failed. $vids");
                exit();
            }
            else {
                /**
                 *  Format video title and set download and file preferences.
                 */
                $title   = self::get_video_title();
                $path    = self::get_downloads_dir();

                $YT_Video_URL = $vids[$c]["url"];
                $res = $vids[$c]["type"];
                $ext = $vids[$c]["ext"];

                $videoTitle    = $title . "_-_" . $res ."_-_youtubeid-$id";
                $videoFilename = "$videoTitle.$ext";
                $thumbFilename = "$videoTitle.jpg";
                $video         = $path . $videoFilename;

                self::set_video($videoFilename);
                self::set_thumb($thumbFilename);

                /**
                 *  PHP doesn't cache information about non-existent files.
                 *  So, if you call file_exists() on a file that doesn't exist,
                 *  it will return FALSE until you create the file. The problem is,
                 *  that once you've created a file, file_exists() will return TRUE -
                 *  even if you've deleted the file meanwhile and the cache haven't
                 *  been cleared! Even though unlink() clears the cache automatically,
                 *  since we don't know which way a file may have been deleted (if it existed),
                 *  we clear the file status cache to ensure a valid file_exists result.
                 */
                clearstatcache();

                /**
                 *  If the video does not already exist in the download directory,
                 *  try to download the video and the video preview image.
                 */
                if(!file_exists($video))
                {
                    $download_thumbs = self::get_download_thumbnail();
                    if($download_thumbs === TRUE) {
                        self::check_thumbs($id);
                    }
                    touch($video);
                    chmod($video, 0775);

                    // Download the video.
                    $download = self::curl_get_file($YT_Video_URL, $video);

                    if($download === FALSE) {
                        throw new Exception("Saving $videoFilename to $path failed.");
                        exit();
                    }
                    else {
                        if($download_thumbs === TRUE) {
                            // Download the video preview image.
                            $thumb = self::get_video_thumb();
                            if($thumb !== FALSE)
                            {
                                $thumbnail = $path . $thumbFilename;
                                self::curl_get_file($thumb, $thumbnail);
                                chmod($thumbnail, 0775);
                            }
                        }
                        return 0;
                    }
                }
                else {
                    return 1;
                }
            }
        }
    }

    public function download_audio()
    {
        $ffmpeg = self::has_ffmpeg();
        if($ffmpeg === FALSE) {
            throw new Exception("You must have Ffmpeg installed in order to use this function.");
            exit();
        }
        else if($ffmpeg === TRUE) {
            self::set_video_quality(1);
            $dl = self::download_video();
            if($dl == 0 || $dl == 1)
            {
                $title = self::get_video_title();
                $path = self::get_downloads_dir();
                $ext = self::get_audio_format();
                $ffmpeg_infile = $path . self::get_video();
                $ffmpeg_outfile = $path . $title .".". $ext;

                if(!file_exists($ffmpeg_outfile))
                {
                    // Whether to log the ffmpeg process.
                    $logging = self::get_ffmpegLogs_active();

                    // Ffmpeg command to convert the input video file into an audio file.
                    $ab = self::get_audio_quality() . "k";
                    $cmd = "ffmpeg -i \"$ffmpeg_infile\" -ar 44100 -ab $ab -ac 2 \"$ffmpeg_outfile\"";

                    if($logging !== FALSE)
                    {
                        // Create a unique log file name per process.
                        $ffmpeg_logspath = self::get_ffmpegLogs_dir();
                        $ffmpeg_logfile = "ffmpeg." . date("Ymdhis") . ".log";
                        $logfile = "./" . $ffmpeg_logspath . $ffmpeg_logfile;
                        self::set_ffmpeg_Logfile($logfile);

                        // Create new log file (via command line).
                        exec("touch $logfile");
                        exec("chmod 777 $logfile");
                    }

                    // Execute Ffmpeg command line command.
                    $Ffmpeg = exec($cmd);
                    $log_it = "echo \"$Ffmpeg\" > \"$logfile\"";
                    $lg = `$log_it`;

                    // If the video did not already existed, delete it (since we probably wanted the soundtrack only).
                    if($dl == 0) {
                        unlink($ffmpeg_infile);
                    }

                    clearstatcache();
                    if(file_exists($ffmpeg_outfile) !== FALSE) {
                        self::set_audio($title .".". $ext);
                        return 0;
                    } else {
                        throw new Exception("Something went wrong while converting the video into $ext format, sorry!");
                        exit();
                    }
                }
                else {
                    self::set_audio($title .".". $ext);
                    return 1;
                }
            }
        } else {
            throw new Exception("Cannot locate your Ffmpeg installation?! Thus, cannot convert the video into $ext format.");
            exit();
        }
    }

    /**
     *  Get filestats for the downloaded audio/video file.
     *  @access  public
     *  @return  mixed   Returns an array containing formatted filestats,
     *                   or (boolean) FALSE if file doesn't exist.
     */
    public function video_stats()
    {
        $file = self::get_video();
        $path = self::get_downloads_dir();

        clearstatcache();
        $filestats = stat($path . $file);
        if($filestats !== FALSE) {
            return array(
                "size" => self::human_bytes($filestats["size"]),
                "created" => date ("d.m.Y H:i:s.", $filestats["ctime"]),
                "modified" => date ("d.m.Y H:i:s.", $filestats["mtime"])
            );
        }
        else { return FALSE; }
    }

    /**
     *  Check if input string is a valid YouTube URL
     *  and try to extract the YouTube Video ID from it.
     *  @access  private
     *  @return  mixed    Returns YouTube Video ID, or (boolean) FALSE.
     */
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
        return (isset($matches[1])) ? $matches[1] : FALSE;
    }

    /**
     *  Get internal YouTube info for a Video.
     *  @access  private
     *  @return  string   Returns video info as string.
     */
    private function get_yt_info()
    {
        $url = sprintf($this->YT_INFO_URL, self::get_video_id());
        return self::curl_get($url);
    }

    /**
     *  Get the public YouTube Info-Feed for a Video.
     *  @access  private
     *  @return  mixed    Returns array, containing the YouTube Video-Title
     *                    and preview image URL, or (boolean) FALSE
     *                    if parsing the feed failed.
     */
    private function get_public_info()
    {
        $url = sprintf($this->YT_BASE_URL . "watch?v=%s", self::get_video_id());
        $url = sprintf($this->YT_INFO_ALT, urlencode($url));
        $info = json_decode(self::curl_get($url), TRUE);

        if(is_array($info) && sizeof($info) > 0) {
            return array(
                "title" => $info["title"],
                "thumb" => $info["thumbnail_url"]
            );
        }
        else { return FALSE; }
    }

    /**
     *  Get formatted video data from the public YouTube Info-Feed.
     *  @access  private
     *  @param   string   $str  Info-File contents for a YouTube Video.
     *  @return  mixed    Returns the formatted YouTube Video-Title as string,
     *                    or (boolean) FALSE if extracting failed.
     */
    private function get_videodata($str)
    {
        $yt_info = $str;
        $pb_info = self::get_public_info();

        if($pb_info !== FALSE) {
            $htmlTitle = htmlentities(utf8_decode($pb_info["title"]));
            $videoTitle = self::canonicalize($htmlTitle);
        }
        else {
            $videoTitle = self::formattedVideoTitle($yt_info);
        }

        if(is_string($videoTitle) && strlen($videoTitle) > 0) {
            self::set_video_title($videoTitle);
            return TRUE;
        }
        else { return FALSE; }
    }

    /**
     *  Get the URL map for a YouTube Video.
     *  @access  private
     *  @param   string   $data  Info-File contents for a YouTube Video.
     *  @return  mixed    Returns an array, containg the Video URL map,
     *                    or (boolean) FALSE if extracting failed.
     */
    private function get_url_map($data)
    {
        preg_match('/stream_map=(.[^&]*?)&/i',$data,$match);
        if(!isset($match[1])) {
            return FALSE;
        }
        else {
            $fmt_url =  urldecode($match[1]);
            if(preg_match('/^(.*?)\\\\u0026/',$fmt_url,$match2)) {
                $fmt_url = $match2[1];
            }

            $urls = explode(',',$fmt_url);
            $tmp = array();

            foreach($urls as $url) {
                if(preg_match('/itag=([0-9]+)&url=(.*?)&.*?/si',$url,$um))
                {
                    $u = urldecode($um[2]);
                    $tmp[$um[1]] = $u;
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

    /**
     *  Get the preview image for a YouTube Video.
     *  @access  private
     *  @param   string   $id  Valid YouTube Video-ID.
     *  @return  mixed    Returns the image URL as string,
     *                    or (boolean) FALSE if no image found.
     */
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
            else { $th = FALSE; }
        }
        self::set_video_thumb($th);
    }

    /**
     *  Get the YouTube Video Title and format it.
     *  @access  private
     *  @param   string   $str  Input string.
     *  @return  string   Returns cleaned input string.
     */
    private function formattedVideoTitle($str)
    {
        preg_match_all('#title=(.*?)$#si', urldecode($str), $matches);

        $title = explode("&", $matches[1][0]);
        $title = $title[0];
        $title = htmlentities(utf8_decode($title));

        return self::canonicalize($title);
    }

    /**
     *  Format the YouTube Video Title into a valid filename.
     *  @access  private
     *  @param   string   $str  Input string.
     *  @return  string   Returns cleaned input string.
     */
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
     *  Replace common special entity codes for special character
     *  vowels by their equivalent ASCII letter.
     *  @access  private
     *  @param   string   $str  Input string.
     *  @return  string   Returns cleaned input string.
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

    /**
     *  Check if given directory exists. If not, try to create it.
     *  @access  private
     *  @param   string   $dir  Path to the directory.
     *  @return  boolean  Returns (boolean) TRUE if directory exists,
     *                    or was created, or FALSE if creating non-existing
     *                    directory failed.
     */
    private function valid_dir($dir)
    {
        if(is_dir($dir) !== FALSE) {
            chmod($dir, 0777); # Ensure permissions. Otherwise CURLOPT_FILE will fail!
            return TRUE;
        }
        else {
            return (bool) ! @mkdir($dir, 0777);
        }
    }

    /**
     *  Check on the command line if we can find an Ffmpeg installation on the script host.
     *  @access  private
     *  @return  boolean  Returns (boolean) TRUE if Ffmpeg is installed on the server,
     *                    or FALSE if not.
     */
    private function has_ffmpeg()
    {
        $sh = `which ffmpeg`;
        return (bool) (strlen(trim($sh)) > 0);
    }

    /**
     *  HTTP HEAD request with curl.
     *  @access  private
     *  @param   string   $url  String, containing the URL to curl.
     *  @return  intval   Returns a HTTP status code.
     */
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

    /**
     *  HTTP GET request with curl.
     *  @access  private
     *  @param   string   $url  String, containing the URL to curl.
     *  @return  string   Returns string, containing the curl result.
     */
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

    /**
     *  HTTP GET request with curl that writes the curl result into a local file.
     *  @access  private
     *  @param   string   $remote_file  String, containing the remote file URL to curl.
     *  @param   string   $local_file   String, containing the path to the file to save
     *                                  the curl result in to.
     *  @return  void
     */
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

    // Getter and Setter for the downloaded audio file.
    public function get_audio() {
        return $this->audio; }
    private function set_audio($audio) {
        $this->audio = $audio; }

    // Getter and Setter for the downloaded video file.
    public function get_video() {
        return $this->video; }
    private function set_video($video) {
        $this->video = $video; }

    // Getter and Setter for the downloaded video preview image.
    public function get_thumb() {
        return $this->thumb; }
    private function set_thumb($img) {
        if(is_string($img)) {
            $this->thumb = $img;
        } else {
            throw new Exception("Invalid thumbnail given: $img"); }
    }

    // Getter and Setter whether to download the video, or convert to audio by default.
    public function get_default_download() {
        return $this->defaultDownload; }
    public function set_default_download($action) {
        if($action == "audio" || $action == "video") {
            $this->defaultDownload = $action;
        } else {
            throw new Exception("Invalid download type. Must be either 'audio', or 'video'."); }
    }

    // Getter and Setter for the video quality.
    public function get_video_quality() {
        return $this->videoQuality; }
    public function set_video_quality($q) {
        if(in_array($q, array(0,1))) {
            $this->videoQuality = $q;
        } else {
            throw new Exception("Invalid video quality."); }
    }

    // Getter and Setter for the audio quality.
    public function get_audio_quality() {
        return $this->audioQuality; }
    public function set_audio_quality($q) {
        if($q >= 128 && $q <= 320) {
            $this->audioQuality = $q;
        } else {
            throw new Exception("Audio sample rate must be between 128 and 320."); }
    }

    // Getter and Setter for the audio output filetype.
    public function get_audio_format() {
        return $this->audioFormat; }
    public function set_audio_format($ext) {
        $valid_exts = array("mp3", "wav", "ogg", "mp4");
        if(in_array($ext, $valid_exts)) {
            $this->audioFormat = $ext;
        } else {
            throw new Exception("Invalid audio filetype '$ext' defined.
            Valid filetypes are: " . implode(", ", $valid_exts) ); }
    }

    // Getter and Setter for the download directory.
    public function get_downloads_dir() {
        return $this->downloadsDir; }
    public function set_downloads_dir($dir) {
        if(self::valid_dir($dir) !== FALSE) {
            $this->downloadsDir = $dir;
        } else {
            throw new Exception("Can neither find, nor create download folder: $dir"); }
    }

    // Getter and Setter whether to log Ffmpeg processes.
    public function get_ffmpegLogs_active() {
        return $this->FfmpegLogsDir; }
    public function set_ffmpegLogs_active($b) {
        $this->FfmpegLogsActive = (bool) ($b !== FALSE); }

    // Getter and Setter for the YouTube URL map.
    public function get_yt_url_map() {
        return $this->downloadsArray; }
    private function set_yt_url_map($videos) {
        $this->downloadsArray = $videos; }

    // Getter and Setter for the YouTube Video-ID.
    public function get_video_id() {
        return $this->videoID; }
    public function set_video_id($id) {
        if(strlen($id) == 11) {
            $this->videoID = $id;
        } else {
            throw new Exception("$id is not a valid Youtube Video ID."); }
    }

    // Getter and Setter for the formatted video title.
    public function get_video_title() {
        return $this->videoTitle; }
    public function set_video_title($str) {
        if(is_string($str)) {
            $this->videoTitle = $str;
        } else {
            throw new Exception("Invalid title given: $str"); }
    }

    // Getter and Setter for thumbnail preferences.
    public function get_download_thumbnail() {
        return $this->downloadThumbs; }
    public function set_download_thumbnail($q) {
        if($q == TRUE || $q == FALSE) {
            $this->downloadThumbs = (bool) $q;
        } else {
            throw new Exception("Invalid argument given to set_download_thumbnail."); }
    }

    // Getter and Setter for the video preview image size.
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

    // Getter and Setter for the object's Ffmpeg log file.
    public function get_ffmpeg_Logfile() {
        return $this->ffmpegLogfile; }
    private function set_ffmpeg_Logfile($str) {
        $this->ffmpegLogfile = $str; }


    // Getter and Setter for the remote video preview image.
    public function get_video_thumb() {
        return $this->videoThumb; }
    private function set_video_thumb($img) {
        $this->videoThumb = $img; }

    // Getter and Setter for the Ffmpeg-Logs directory.
    public function get_ffmpegLogs_dir() {
        return $this->FfmpegLogsDir; }
    public function set_ffmpegLogs_dir($dir) {
        if(self::valid_dir($dir) !== FALSE) {
            $this->FfmpegLogsDir = $dir;
        } else {
            throw new Exception("Can neither find, nor create ffmpeg log directory '$dir', but logging is enabled."); }
    }

    /**
     *  Format file size in bytes into human-readable string.
     *  @access  public
     *  @param   string   $bytes   Filesize in bytes.
     *  @return  string   Returns human-readable formatted filesize.
     */
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
