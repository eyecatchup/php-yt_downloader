<?php
  /**
   * Config interface for the yt_downloader class.
   */
  interface cnfg
  {
      /**
       *  Set directory to save the downloads to.
       */
      const Download_Folder = 'videos/';

      /**
       *  Set video quality.
       *  Choose '1' to download videos in the best quality available, 
       *  or '0' for the lowest quality (,thus smallest file size).
       */
      const Default_Videoquality = 0;

      /**
       *  Set thumbnail size.
       *  Choose one of 'l' (480*360px), or 's' (120*90px).
       */
      const Default_Thumbsize = 'l';
  }
