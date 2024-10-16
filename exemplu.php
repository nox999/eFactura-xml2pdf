<?php

  require_once('tcpdf/tcpdf.php'); // https://github.com/tecnickcom/tcpdf
  require_once('fpdi/src/autoload.php'); // este opțional dacă se dorește includerea anexelor în format PDF (https://github.com/Setasign/FPDI)
  require_once('xml2pdf.php');

  $xmlString=file_get_contents('exemplu.xml');
  $factura=xml2pdfParse($xmlString);

  if ($factura===false) {
    exit('Eroare la parcurgerea fișierului XML.');
  }

  $r=xml2pdfRender($factura,true);
  header("Content-type:application/pdf");
  echo $r;

?>
