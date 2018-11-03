# mconv
Photo Image and Video converter shrinker written in PHP for Mac OSX , Linux and possibly Windows 

Video conversion is dependant on videolan ffmpeg binary,

Image conversion requieres GD php library

## Dependancy
* PHP 7+
* GD extension
* ffmpeg for video conversion

This library can be used in other PHP projects or as standalone with mconv CLI tool , **mconv** stands for media converter 

Main purpose of this tool is to **save space but retain image and video quality** 

## Installing
* `brew install ffmpeg` OSX
* `composer require btesanovic/mconv`

## Usage
Lets say you want to save space by converting all video files to **mp4** ( this is default video output format )

### Example 1

* Input folder `~/Movies`
* Input formats `mpg avi mov`
* Output format `mp4`
* -g Stands for glob "**\***" denotes all files you could use "2018*" to convert all files starting with file name **2018** that have any of extensions mentioned above `mpg avi mov`

 Command
 
  `mconv -i ~/Movies -i "mpg,avi,mov" -o mp4 -g "*"`
  
### Example 2
 Delete all originally converted files
 
 * Input folder `~/Movies`
 Command
 
  `mconv -i ~/Movies -d`

 

