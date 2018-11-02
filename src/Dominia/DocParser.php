<?php
/**
 * Created by Pangodream.
 * User: Development
 * Date: 30/10/2018
 * Time: 13:37
 */

namespace Dominia;

use \Smalot\PdfParser\Parser;

class DocParser
{
    /**
     * Reads the specified PDF file (complete path & filename), decode and parse it
     * and returns an array containing as many elements as domains inside the PDF file.
     * Each array element is an associative array of type ('domain' => domain.com, 'registerDate'=>yyyy-mm-dd)
     *
     * @param $pdfFile
     * @return array
     * @throws \Exception
     */
    public function pdfToArray($pdfFile){
        $domains = array();
        $lines = array();
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($pdfFile);
        $text = $pdf->getText();
        $rawLines = explode("\n", $text);
        $line = "";
        foreach($rawLines as $l){
            $l = trim($l);
            $line .= $l;
            if($this->isValidDate(substr($line, -10))){
                $lines[] = $line;
                $line = "";
            }
        }
        foreach($lines as $l) {
            $l = trim($l);
            $domainEntry = trim(substr($l, 0, -10));
            $dateOrig = trim(substr($l, -10));
            $date = substr($dateOrig, 6, 4)."-".substr($dateOrig, 3, 2)."-".substr($dateOrig, 0, 2);
            if($this->isValidDate($date) && $this->isValidDomain($domainEntry)) {
                $domains[] = array('domain' => $domainEntry, 'registerDate' => $date);
            }
        }
        return $domains;
    }
    private function isValidDate($date){
        return checkdate((int)substr($date, 3, 2),
                         (int)substr($date, 0, 2),
                         (int)substr($date, 6, 4));
    }
    private function isValidDomain($domain){
        return filter_var("http://".$domain, FILTER_VALIDATE_URL);
    }
}