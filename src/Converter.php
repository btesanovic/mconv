<?php

namespace btesanovic\mconv;

class Converter
{

    const PREFIX='mconv_';
    protected $inputFormats = [];
    protected $outputFormat = '';
    protected $toCovnert=[];
    protected $alreadyCovnerted=[];



    public function __construct()
    {
        # code...
    }

    public function setInputFormats(array $inf)
    {
        foreach($inf as $if){
            //remove any dots
            $this->inputFormats[strtolower( trim( $if ,'.') )] = 1;
        }
        //$this->inputFormats = $inf;
    }
    public function setOutputFormat( $outputFormat)
    {
        $this->outputFormat = trim( $outputFormat ,'.');
    }

    public function fillRescursive($path, $glob)
    {
        if (!is_dir($path)) {
            throw new \Exception("$path is not a directory");
        }
        if (!$glob) {
            throw new \Exception("$glob must be defiend , for all files just put '*'");
        }
        $this->getFilesRecursive($path);


        foreach($this->files as $path => $filename ){

            if(fnmatch(self::PREFIX .'*' , $filename)){
                echo "already converted $filename\n";
                $extNew = $this->getExtension($filename);
                $ogFile = str_replace(self::PREFIX , '' , $path);
                $ogFile = str_replace('.'.$extNew ,'',$ogFile);
                $this->alreadyCovnerted[$ogFile]= $path;
                continue;
            }

            if(fnmatch($glob , $filename )){
                if( $this->isInputFormat($filename) ){
                    //echo "macthed $filename\n";
                    $this->convert($path , $this->outputFormat);
                }else{
                    //echo "glob matched but input format is did not $filename \n";
                }
            }else{
                //echo " - skipping $filename\n";
            }
        }

        if(!$this->toCovnert){
            echo "nothing to convert\n";
        }
        

    }

    public function convert($inputFilePath , $outputFormat ){
        $dir = dirname($inputFilePath);
        $baseName = basename($inputFilePath);
        $outFname = self::PREFIX . $baseName . '.'. $outputFormat;
        $outputFilePath = $dir .'/' . $outFname;
        if(is_file($outputFilePath)){
            echo "Already converted\n";
            return;
        }
        $this->toCovnert[$inputFilePath] = $outputFilePath; 
        //$this->videoConvert($inputFilePath , $outputFilePath , $outputFormat);
    }

    protected $files=[];
    public function getFilesRecursive($path)
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

        $files = array();

        $cnt = 0;
        foreach ($rii as $file => $fObj) {

            if ($rii->isDir()) {
                continue;
            }

            $files[$rii->getPathname()] = $rii->current()->getFilename();

        }

        $this->files = $files;
        

    }
    protected $deleteAfterConversion=false;
    public function deleteAfter($flag){
        $this->deleteAfterConversion = $flag  ? true : false;
    }

    public function deleteConverted($path  ){
        if( ! $this->deleteAfterConversion) return;
            $this->fillRescursive($path , '*');
            $this->_deleteConverted();
    }

    protected function _deleteConverted(){
        if( ! $this->deleteAfterConversion) return;

        foreach($this->alreadyCovnerted as $path=>$convPath ){
            echo "Deleting $path\n";
            $og = round(filesize($path)/(1024*1024) ,1);
            $conv = round(filesize($convPath)/(1024*1024) ,1);
            echo "Original: $og Mb  '$path'\n";
            echo "Converted:$conv Mb '$convPath''\n";
            $answ = $this->ask("Delete hit 'c' to delte converted file ") ;
            if($answ== 'y'){            
                unlink($path);
            }
            if($answ == 'c'){            
                unlink($convPath);
            }
        }
    }

    public function ask($q){
        echo "$q [Y/n]? \n";
        $a = strtolower(trim(fgets(STDIN)));
        return $a;
    }

    public function getExtension($file){
        return pathinfo($file, PATHINFO_EXTENSION);

    }

    public function isInputFormat($file ){
        $ext = strtolower( $this->getExtension($file));
        if(isset($this->inputFormats[$ext])) return true;

        return false;
        
    }
    public function getFiles()
    {

    }
}
