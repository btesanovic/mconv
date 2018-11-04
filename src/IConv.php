<?php
namespace btesanovic\mconv;

class IConv extends Converter
{

    protected $command;
    protected $inputFormatAuto = ['png','jpg','jpeg'];
    protected $outputFormatAuto = 'jpg';
    protected $outputFormat = 'jpg';
    protected $auto=false;

    public function __construct()
    {
    }

    //public function setOutputFormat($whatever){
        //\trigger_error("Only JPG supported" , E_USER_NOTICE);
    //}


    public function auto($bool){
        if($bool){
        $this->setOutputFormat ( $this->outputFormatAuto );
        $this->setInputFormats ( $this->inputFormatAuto );
        $this->auto = true;
        }
    }

    protected $max=null;
    public function setMax($max){
        $this->max = $max;
    }
   

    public function convertRecursive($path, $glob)
    {
        //var_dump($path , $glob);
        //exit();
        $this->fillRescursive($path, $glob);

        echo "Following files will be converted \n";
        foreach ($this->toCovnert as $in => $out) {
            echo "$in\n";
        }

        echo "=====\n";

        echo "Proceed hit [Y] to convert?: \n";
        $answ = strtolower(trim(fgets(STDIN)));
        if ($answ == 'y') {
            foreach ($this->toCovnert as $in => $out) {
                $this->shrinkImage($in, $out, $this->outputFormat);
            }
        } else {
            echo "Aborting conversion\n";
           
        }

        $this->_deleteConverted();

    }

    public function shrinkImage($full, $out , $outputformat)
    {

        $msize = filesize($full);
        $last_modification = filemtime($full);
        //$source = imagecreatefromjpeg($full);
        $source  = $this->getImageCreateData($full);

        if (!$source) {
            echo "Could not read image file $full";
            return;
        }

        if($this->max > 100 ){
            $outSource = $this->resizeMaxWorH($source , $this->max );
            if(!$outSource){
                echo "could not resize \n";
                return;
            }else{
                imagejpeg($outSource, $out, 90);

            }
        }else{
            // Output
            imagejpeg($source, $out, 90);
        }
        $gain=-100;
        if ($this->transferIptcExif2File($full, $out)) {
            touch($out, $last_modification);
            $newsize = filesize($out);
            $gain = round($msize / $newsize, 2);
            echo "Gain $gain \n";
            //unlink($master);
        } else {
            echo "unable to transfer exif data";
            touch($out, $last_modification);
            $newsize = filesize($out);
            $gain = round($msize / $newsize, 2);
            echo "Gain $gain \n";
        }

        if($this->auto ){
            if($gain > 1){
                \unlink($full);
                \rename($out , $full);
            }else{
                unlink($out);
            }
        }


    }

    protected function getImageCreateData($imagepath){
        return \imagecreatefromstring(\file_get_contents($imagepath));

        $ext = $this->getExtension($imagepath);
        switch($ext) {
            case 'jpg':
            case 'jpeg':
            return imagecreatefromjpeg($imagepath);

            case 'png':
            return imagecreatefrompng($imagepath);
            case 'bmp': //in php 7.2+
            return imagecreatefrombmp ($imagepath);
        }

        return null;
    }

    public function transferIptcExif2File($srcfile, $destfile)
    {
        // Function transfers EXIF (APP1) and IPTC (APP13) from $srcfile and adds it to $destfile
        // JPEG file has format 0xFFD8 + [APP0] + [APP1] + ... [APP15] + <image data> where [APPi] are optional
        // Segment APPi (where i=0x0 to 0xF) has format 0xFFEi + 0xMM + 0xLL + <data> (where 0xMM is
        //   most significant 8 bits of (strlen(<data>) + 2) and 0xLL is the least significant 8 bits
        //   of (strlen(<data>) + 2)

        if (file_exists($srcfile) && file_exists($destfile)) {
            $srcsize = @getimagesize($srcfile, $imageinfo);
            // Prepare EXIF data bytes from source file
            $exifdata = (is_array($imageinfo) && key_exists("APP1", $imageinfo)) ? $imageinfo['APP1'] : null;
            if ($exifdata) {
                $exiflength = strlen($exifdata) + 2;
                if ($exiflength > 0xFFFF) {
                    return false;
                }

                // Construct EXIF segment
                $exifdata = chr(0xFF) . chr(0xE1) . chr(($exiflength >> 8) & 0xFF) . chr($exiflength & 0xFF) . $exifdata;
            }
            // Prepare IPTC data bytes from source file
            $iptcdata = (is_array($imageinfo) && key_exists("APP13", $imageinfo)) ? $imageinfo['APP13'] : null;
            if ($iptcdata) {
                $iptclength = strlen($iptcdata) + 2;
                if ($iptclength > 0xFFFF) {
                    return false;
                }

                // Construct IPTC segment
                $iptcdata = chr(0xFF) . chr(0xED) . chr(($iptclength >> 8) & 0xFF) . chr($iptclength & 0xFF) . $iptcdata;
            }
            $destfilecontent = @file_get_contents($destfile);
            if (!$destfilecontent) {
                return false;
            }

            if (strlen($destfilecontent) > 0) {
                $destfilecontent = substr($destfilecontent, 2);
                $portiontoadd = chr(0xFF) . chr(0xD8); // Variable accumulates new & original IPTC application segments
                $exifadded = !$exifdata;
                $iptcadded = !$iptcdata;

                while ((substr($destfilecontent, 0, 2) & 0xFFF0) === 0xFFE0) {
                    $segmentlen = (substr($destfilecontent, 2, 2) & 0xFFFF);
                    $iptcsegmentnumber = (substr($destfilecontent, 1, 1) & 0x0F); // Last 4 bits of second byte is IPTC segment #
                    if ($segmentlen <= 2) {
                        return false;
                    }

                    $thisexistingsegment = substr($destfilecontent, 0, $segmentlen + 2);
                    if ((1 <= $iptcsegmentnumber) && (!$exifadded)) {
                        $portiontoadd .= $exifdata;
                        $exifadded = true;
                        if (1 === $iptcsegmentnumber) {
                            $thisexistingsegment = '';
                        }

                    }
                    if ((13 <= $iptcsegmentnumber) && (!$iptcadded)) {
                        $portiontoadd .= $iptcdata;
                        $iptcadded = true;
                        if (13 === $iptcsegmentnumber) {
                            $thisexistingsegment = '';
                        }

                    }
                    $portiontoadd .= $thisexistingsegment;
                    $destfilecontent = substr($destfilecontent, $segmentlen + 2);
                }
                if (!$exifadded) {
                    $portiontoadd .= $exifdata;
                }
                //  Add EXIF data if not added already
                if (!$iptcadded) {
                    $portiontoadd .= $iptcdata;
                }
                //  Add IPTC data if not added already
                $outputfile = fopen($destfile, 'w');
                if ($outputfile) {
                    return fwrite($outputfile, $portiontoadd . $destfilecontent);
                } else {
                    return false;
                }

            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function resizeMaxWorH($original , $max){
        $originalWidth = imagesx($original);
        $originalHeight = imagesy($original);
        $targetWidth = $max;
        $targetHeight = $max;
        $widthRatio = $max / $originalWidth;
        $heightRatio = $max / $originalHeight;

        
        if(($widthRatio >= 1 || $heightRatio >= 1) ){
            // don't scale up an image if either targets are greater than the original sizes and we aren't using a strict parameter
            $dstHeight = $originalHeight;
            $dstWidth = $originalWidth;
            $srcHeight = $originalHeight;
            $srcWidth = $originalWidth;
            $srcX = 0;
            $srcY = 0;
            echo "image already small\n";
            return false;
        }elseif ($widthRatio > $heightRatio) {
            // width is the constraining factor
            
                $dstHeight = ($originalHeight * $targetWidth) / $originalWidth;
                $dstWidth = $targetWidth;
                $srcHeight = $originalHeight;
                $srcWidth = $originalWidth;
                $srcX = 0;
                $srcY = 0;
            
        } else {
            // height is the constraining factor
            
                $dstHeight = $targetHeight;
                $dstWidth = ($originalWidth * $targetHeight) / $originalHeight;
                $srcHeight = $originalHeight;
                $srcWidth = $originalWidth;
                $srcX = 0;
                $srcY = 0;
            
        }



        $new = imagecreatetruecolor($dstWidth, $dstHeight);
        if ($new === false) {
            return false;
        }

        imagecopyresampled($new, $original, 0, 0, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

        return $new;
    }

}
