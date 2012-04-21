<?php
interface cnfg
{
	# Define folder to save videos to.
	const Download_Folder 		= 'videos/';
	
	# Choose '1' to download best quality available, or '0' for smallest file size.
	const Default_Videoquality 	= 0;
	
	# Choose one of 'l' (480*360px), or 's' (120*90px).
	const Default_Thumbsize 	= 'l';
}