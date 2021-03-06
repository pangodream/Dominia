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
            preg_match_all('#[0-3][0-9]\/[0-1][0-9]\/[2][0][0-9][0-9]#', $l, $found);
            foreach($found[0] as $fDate){
                $i = strpos($l, $fDate);
                $dateOrig = substr($l, $i, 10);
                $domainEntry = trim(substr($l, 0, $i));
                if($this->isValidDate($dateOrig) && $this->isValidDomain($domainEntry)) {
                    $date = substr($dateOrig, 6, 4)."-".substr($dateOrig, 3, 2)."-".substr($dateOrig, 0, 2);
                    $domains[] = array('domain' => $domainEntry, 'registerDate' => $date);
                }
                $l = substr($l, $i + 10);
            }
        }
        return $domains;
    }

    /**
     * Checks if a date string is valid
     * @param $date
     * @return bool
     */
    private function isValidDate($date){
        return preg_match('#[0-3][0-9]\/[0-1][0-9]\/[2][0][0-9][0-9]#', $date)
               &&
               checkdate((int)substr($date, 3, 2),
                         (int)substr($date, 0, 2),
                         (int)substr($date, 6, 4));
    }

    /**
     * Checks if a string is a valid domain name
     * @param $domain
     * @return mixed
     */
    private function isValidDomain($domain){
        return filter_var("http://".$domain, FILTER_VALIDATE_URL);
    }
}