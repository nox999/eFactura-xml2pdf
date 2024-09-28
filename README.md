# eFactura-xml2pdf
Script PHP care transformă facturile din format XML în format PDF pentru tipărire și vizualizare.
## De ce există?
Ministerul de Finanțe pune la dispoziție o "aplicație" care transformă facturile din format XML în PDF. Din păcate rezultatele pe care le produce sunt aproape ilizibile. Totuși, cei care vor să-și facă singuri implementarea eFactură se vor lovi mai devreme sau mai târziu de nevoia de a vedea facturile primite de la furnizori și își vor da seama că [uneltele](https://mfinante.gov.ro/ro/web/efactura/aplicatii-web-ro-efactura) puse la dispoziție de autorități sunt extrem de proaste.
## Ce am urmărit?
* Simplu, flexibil și ușor de înțeles (tot codul este procedural și comentat);
* Nu folosește alte librării externe în afară de [TCPDF](https://github.com/tecnickcom/tcpdf);
* Compatibil cu PHP 5-8;
* Este publicat sub licența [GPLv3](https://www.gnu.org/licenses/gpl-3.0.en.html). Contribuțiile sunt binevenite;
* Pentru că standardul eFactură este stufos, prost, ambiguu și redundant am ales să acoperim doar cazurile folosite de marea majoritate a companiilor. Contribuțiile sunt binevenite.
## Cum se folosește?
```php
<?php

  require_once('tcpdf/config/lang/ron.php');
  require_once('tcpdf/tcpdf.php');
  require_once('xml2pdf.php');

  $xml=file_get_contents('factura.xml'); // citește fișierul XML descărcat în prealabil prin API SPV

  $factura=xml2pdfParse($xml); // generează un array cu câmpurile relevante din factură

  // varianta 1:

  xml2pdfRender($factura); // generează și trimite inline (afișează în browser) un fișier PDF

  // varianta 2:

  $pdf=xml2pdfRender($factura,true); // generează și returnează un fișier PDF
  header("Content-type:application/pdf");
  echo $pdf;

?>
```
## Cum arată documentele generate?
Deoarece **TCPDF** este o librărie complexă și presupune o oarecare experiență în folosire pentru rezultate optime, am creat o serie de instrucțiuni de bază (text, linie, dreptunghi etc) care se folosesc în generarea documentului. Drept urmare nu este necesară învățarea **TCPDF**.

### Factură PDF generată pe mfinante.ro
![anaf](https://github.com/user-attachments/assets/fe07d762-477f-4a77-9f05-fc1699f2faba)

### Factură PDF generată de xml2pdf.php
![xml2pdf](https://github.com/user-attachments/assets/efe646bf-8ec4-4598-a3a0-457391db4dff)

