<?php
    //this is a test script to count vgWort pixel insertions
    //note that counts will be different depending on whether or not the pdfviewer plugin is enabled

    define('VERBOSE',FALSE);

    //define individual urls to check
    define('URLs',array(
        'http://ojs-test.cedis.fu-berlin.de/ojs-rs-312/index.php/OJS312TJ/index',//issue page
        'http://ojs-test.cedis.fu-berlin.de/ojs-rs-312/index.php/OJS312TJ/article/view/8',//pdf galley
        'http://ojs-test.cedis.fu-berlin.de/ojs-rs-312/index.php/OJS312TJ/article/view/9',//epub galley
        'http://ojs-test.cedis.fu-berlin.de/ojs-rs-312/index.php/OJS312TJ/article/view/8/9',//pdfviewer
        'http://ojs-test.cedis.fu-berlin.de/ojs-rs-311/index.php/ojsrs311',//issue page
        'http://ojs-test.cedis.fu-berlin.de/ojs-rs-311/index.php/ojsrs311/article/view/6',//pdf galley
        'http://ojs-test.cedis.fu-berlin.de/ojs-rs-311/index.php/ojsrs311/article/view/8',//epub galley
        'http://ojs-test.cedis.fu-berlin.de/ojs-rs-311/index.php/ojsrs311/article/view/6/7'//pdfviewer page
        ));

    $curlCh = curl_init();
    
    foreach (URLs as $url) {
        
        curl_setopt($curlCh, CURLOPT_URL, $url);
        curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, 1);
        
        $output = curl_exec($curlCh);
        $curlError = curl_error($curlCh);
        if ($curlError) {
            // error occured
            print_r($curlError);
        }
        
        print_r($url.PHP_EOL);
        if (!(strlen($output) > 0)) {
            //requesting a pdfviewer page from OJS 3.1.2 via curl returns an empty page, no idea why, works fine for OJS 3.1.1 
            print_r("Error curl retuend an empty page.".PHP_EOL);
        }
        
        $matches = array();
        //get script insertions
        preg_match_all('#vgwPixelCall\(galleyId\)#', $output, $matches);
        print_r("script vgwPixelCall found: ".count($matches[0])."\n");
        //get onclick insertions
        preg_match_all('#<a (.+) onclick="vgwPixelCall([\s\S]*?)</a>#', $output, $matches);
        print_r("calls to vgwPixelCall found: ".count($matches[0])."\n");
        if (VERBOSE) {print_r($matches[0]);print_r($output);}
        //get viewer insertions
        preg_match_all('#<div id="div_vgwpixel">#', $output, $matches);
        print_r("pdfviewer insertions found: ".count($matches[0])."\n");
    }
?>