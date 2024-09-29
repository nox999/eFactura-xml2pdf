<?php

  require_once('tcpdf/tcpdf.php');
  require_once('xml2pdf.php');

  $xmlString=file_get_contents('factura.xml');
  $factura=xml2pdfParse($xmlString);

  if ($factura===false) {
    exit('Eroare la parcurgerea fișierului XML.');
  }

  $r=xml2pdfRender($factura,true);
  header("Content-type:application/pdf");
  echo $r;

?>
