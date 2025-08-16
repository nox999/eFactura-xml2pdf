<?php

  require_once('../libs/tcpdf/tcpdf.php'); // https://github.com/tecnickcom/tcpdf
  require_once('../libs/fpdi/src/autoload.php'); // este opțional dacă se dorește includerea anexelor în format PDF (https://github.com/Setasign/FPDI)
  require_once('../src/xml2pdf.php');

  $xmlString=file_get_contents('exemplu.xml');
  $factura=xml2pdfParse($xmlString);

  if (!is_array($factura)) {
    exit('Eroare la parcurgerea fișierului XML. Cod eroare: '.$factura);
  }

  xml2pdfRender($factura,false,'factura {{furnizor}} ({{numar}} din {{data}})');

?>

