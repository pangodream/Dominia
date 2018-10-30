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
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($pdfFile);
        $text = $pdf->getText();
        $lines = explode("\n", $text);
        foreach($lines as $l) {
            $l = trim($l);
            $domainEntry = trim(substr($l, 0, -10));
            $dateOrig = trim(substr($l, -10));
            $date = substr($dateOrig, 6, 4).'-'.substr($dateOrig, 3, 2).'-'.substr($dateOrig, 0, 2);
            if (filter_var("http://".$domainEntry, FILTER_VALIDATE_URL)) {
                //echo $date . ": " . $dominio . "\n";
                $domains[] = array('domain'=>$domainEntry, 'registerDate'=>$date);
            }
        }
        return $domains;
    }
}