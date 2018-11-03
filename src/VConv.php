<?php
namespace btesanovic\mconv;

class VConv extends Converter {


    protected $command;
    protected $outputFormat = 'mp4';
    protected $ffmpeg='';

    public function __construct($ffmpegPath = null )
    {
        parent::__construct();
        if(!$ffmpegPath){
            $this->findFFMPEG();
        }else{
            $this->setFFMPEGpath($ffmpegPath);
        }

        if(!$this->ffmpeg){
            throw new \Exception("ffmpeg binary could not be found\n");
        }

        $this->command= $this->ffmpeg .' -hide_banner -y -i "%s" -f %s -vcodec libx264 -preset fast -acodec aac  -filter:a "volume=1.2" "%s"';
    }

    public function findFFMPEG(){
        //$found = `type ffmpeg`;
        $found = trim(`which ffmpeg`);
        if($found){
            $this->ffmpeg = $found;
            echo " !! FOUND ffmpeg at $found\n";
        }else{
            echo "Could not find ffmpeg binary , ensure it is in your PATH\nor set it manually via ' new Vconf(\$ffmpegfullpath); '\n";
        }
    }

    public function setFFMPEGpath($ffpath){
        if(!is_file($ffpath)){
            throw new \Exception("$ffpath could not be found\n");
        }
        $this->ffmpeg = $ffpath;
    }

    public function convertRecursive($path, $glob){
        //var_dump($path , $glob);
        //exit();
        $this->fillRescursive($path , $glob);
        
        echo "Following files will be converted \n";
        foreach($this->toCovnert as $in=>$out){
            echo "$in\n";
        }


        echo "=====\n";

        


        echo "Proceed hit [Y] to convert?: \n";
        $answ = strtolower(trim(fgets(STDIN)));
        if($answ =='y'){
            foreach($this->toCovnert as $in=>$out){
                $this->videoConvert($in  , $out  , $this->outputFormat);
            }
        }else{
            echo "Aborting conversion\n";
            return;
        }

        $this->_deleteConverted();

    }

    

    protected function videoConvert($input , $output , $outputFormat){
        //echo `/usr/local/bin/ffmpeg -hide_banner -y -i "$full" -f mp4 -vcodec libx264 -preset fast -acodec aac  -filter:a "volume=1.5" "$out"`;
        $cmd = sprintf($this->command , $input , $outputFormat , $output);
        echo " *** Converting $input ... \n";
        echo $cmd ."\n";
        echo `$cmd`;
        echo "\n";
    }


}