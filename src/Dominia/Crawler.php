<?php
/**
 * Created by Pangodream.
 * User: Development
 * Date: 30/10/2018
 * Time: 12:32
 */

namespace Dominia;

use GuzzleHttp\Client;

class Crawler
{
    private $uriBase = null;
    private $excludes = array("Estad",
        "Dominios ",
        "Dominios%20",
        "Presentac",
        "Agentes",
        "_dominios_",
        "estad%C3%ADsticas",
        "Caracterizaci",
        "folleto",
        "certificado", );
    private $mainPage = "/dominios/es/todo-lo-que-necesitas-saber/estadisticas";
    private $client = null;
    private $docsHome = null;

    public function __construct($uriBase = "https://www.dominios.es", $requestTimeout = 30.0){
        $this->uriBase = $uriBase;
        $this->client = new Client(['base_uri' => $uriBase,
            'timeout' => $requestTimeout, ]);
    }
    public function extract(){
        $count = 0;
        if ($this->docsHome == null){
            die('Error. You must first set documents home (setDocsHome)');
        }
        $mainContent = $this->getMainPage();
        $documentsLink = $this->extractDocumentsLink($mainContent);
        foreach($documentsLink as $link){
            $processed = $this->processLink($link);
            if($processed == true){
                $count++;
            }
        }
        return $count;
    }
    public function setDocsHome($docsHome){
        $this->docsHome = $docsHome;
    }
    public function listLocalFiles(){
        $files = array();
        $dir = dir($this->docsHome);
        while (false !== ($file = $dir->read())) {
            if(substr($file, -4)==".pdf"){
                $files[] = array('path'=>$this->docsHome, 'fileName'=>$file);
            }
        }
        return $files;
    }
    private function getMainPage(){
        $mainContent = $this->getDocument($this->mainPage);
        return $mainContent;
    }
    private function getDocument($query){
        $response = $this->client->request('GET', $query);
        $content = $response->getBody();
        return $content;
    }
    private function extractDocumentsLink($html){
        $links = array();
        //preg_match_all("#<a\ href=\"(.*)\.pdf\"#", $html, $matches);
        preg_match_all("#<a\ href=\"(.*)\"#", $html, $matches);
        foreach($matches[1] as $f) {
            $pospdf = strpos($f,".pdf");
            if($pospdf !== false) {
                $f = substr($f, 0, $pospdf + 4);
                if (!$this->excludeLink($f)) {
                    $links[] = $f;
                }
            }
        }
        return $links;
    }
    private function excludeLink($href){
        //If href string contains any of the 'words' in $excludes array, return true
        $excluded = false;
        foreach($this->excludes as $word){
            if(strpos($href, $word) !== false){
                $excluded = true;
                break;
            }
        }
        return $excluded;
    }
    private function processLink($link){
        $processed = false;
        $hash = md5($link);
        $filename = $this->getFilename($link);
        if(!$this->existePdf($filename, $hash)){
            //echo $link . "\n";
            $content = $this->getDocument($link);
            $this->saveFile($filename, $content, $hash);
            $processed = true;
            echo $link . "\n";
        }else{
            echo "Skipped\n";
        }
        return $processed;
    }
    private function getFilename($link){
        $parts = explode("/", $link);
        return $parts[sizeof($parts)-1];
    }
    private function saveFile($filename, $content, $hash){
        $ruta = realpath($this->docsHome).'/'.$hash."-".$filename;
        file_put_contents($ruta, $content);
    }
    private function existePdf($filename, $hash){
        $ruta = realpath($this->docsHome).'/'.$hash."-".$filename;
        return file_exists($ruta);
    }
}