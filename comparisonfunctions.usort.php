<?php
  /**
   * Comparison functions used with usort
   * to sort videos from the YT URL map by quality.
   */

  function asc_by_quality($val_a, $val_b) 
  {
      $a = $val_a['pref'];
      $b = $val_b['pref'];
      if ($a == $b) return 0;
      return ($a < $b) ? -1 : +1;
  }

  function desc_by_quality($val_a, $val_b) 
  {
      $a = $val_a['pref'];
      $b = $val_b['pref'];
      if ($a == $b) return 0;
      return ($a > $b) ? -1 : +1;
  }
