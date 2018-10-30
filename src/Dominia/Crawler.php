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

    /**
     * Crawler constructor.
     *
     * @param string $uriBase          Base of dominios.es uri (def: https://www.dominios.es)
     * @param float $requestTimeout    Request timeout in seconds (def: 30.0)
     */
    public function __construct($uriBase = "https://www.dominios.es", $requestTimeout = 30.0){
        $this->uriBase = $uriBase;
        $this->client = new Client(['base_uri' => $uriBase,
            'timeout' => $requestTimeout, ]);
    }

    /**
     * Main crawler function.
     * It retrieves the content of the main dominios.es page to extract the links to pdf documents
     * It returns the number of processed files (the previously downloaded files are skipped)
     * @return int   Processed files
     */
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

    /**
     * Establish the folder where files will be stored
     * @param $docsHome   Path to docs home, the folder where pdf files will be downloaded to
     */
    public function setDocsHome($docsHome){
        $this->docsHome = $docsHome;
    }

    /**
     * List the local pdf files stored in Docs Home
     * @return array Array containing the pdf files entries. Each entry is an associative array
     *               of type ('path'=>docs home folder, 'fileName'=>nameOfFile.pdf)
     */
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

    /**
     * Returns the main page content (html)
     * @return \Psr\Http\Message\StreamInterface
     */
    private function getMainPage(){
        $mainContent = $this->getDocument($this->mainPage);
        return $mainContent;
    }

    /**
     * Retrieves the content of the document specified by the $query parameter
     * @param $query   QueryString of pdf document
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getDocument($query){
        $response = $this->client->request('GET', $query);
        $content = $response->getBody();
        return $content;
    }

    /**
     * Parses the main page html to extract the links to pdf documents
     * @param $html   html content of the main page
     * @return array  Array containing all the pdf documents links
     */
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

    /**
     * Evaluates if a link to a pdf document is not a valid domains document pdf and should be excluded
     * @param $href   Link to the pdf document
     * @return bool   Evaluation result
     */
    private function excludeLink($href)
    {
        //If href string contains any of the 'words' in $excludes array, return true
        $excluded = false;
        foreach ($this->excludes as $word) {
            if (strpos($href, $word) !== false) {
                $excluded = true;
                break;
            }
        }
        return $excluded;
    }

    /**
     * Retrieve and save a pdf document specified by $link parameter
     * @param $link   QueryString to pdf document
     * @return bool   True if the document is new and not skipped
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
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

    /**
     * Extracts the filename of the pdf document from the specified link
     * @param $link
     * @return mixed
     */
    private function getFilename($link){
        $parts = explode("/", $link);
        return $parts[sizeof($parts)-1];
    }

    /**
     * Calculates a hash for the file and stores the content in docs home folder
     * @param $filename
     * @param $content
     * @param $hash
     */
    private function saveFile($filename, $content, $hash){
        $ruta = realpath($this->docsHome).'/'.$hash."-".$filename;
        file_put_contents($ruta, $content);
    }

    /**
     * Checks if a file exists in docs home folder
     * @param $filename
     * @param $hash
     * @return bool
     */
    private function existePdf($filename, $hash){
        $ruta = realpath($this->docsHome).'/'.$hash."-".$filename;
        return file_exists($ruta);
    }
}