<?php
  /**
   * Config interface for the yt_downloader class.
   */
  interface cnfg
  {
      /**
       *  Set the directory (relative path to class file)
       *  where to save the downloads to.
       */
      const Download_Folder = 'videos/';
	
      /**
       *  Set the video quality.
       *  Choose '1' (nummeric One) to download videos in the best quality available, 
       *  or '0' (nummeric Zero) for the lowest quality (,thus smallest file size).
       */
      const Default_Videoquality = 1;
	
      /**
       *  Set the video preview image size.
       *  Choose 'l' (small letter "L") for a size of 480*360px, 
       *  or 's' for a size of 120*90px.
       */
      const Default_Thumbsize = 'l';
  }
