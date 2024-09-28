# eFactura-xml2pdf
Librărie PHP care transformă facturile din format XML în format PDF pentru tipărire și vizualizare.
## De ce există?
Ministerul de Finanțe pune la dispoziție o ["aplicație"](https://www.anaf.ro/uploadxml/) care transformă facturile din XML în PDF (serviciu disponibil și prin API). Din păcate rezultatele pe care le produce sunt aproape ilizibile și complet deconectate de ce ar avea nevoie o companie reală - un obstacol inutil pentru cei care vor să-și facă singuri implementarea eFactură.
## Ce am urmărit?
* Simplitate și flexibilitate (tot codul este procedural și comentat);
* Nu se folosesc alte librării externe în afară de [TCPDF](https://github.com/tecnickcom/tcpdf);
* Compatibil cu PHP 5-8;
* Publicat sub licența [GPLv3](https://www.gnu.org/licenses/gpl-3.0.en.html). Contribuțiile sunt binevenite;
* Pentru că standardul eFactură este stufos, redundant, prost documentat, ambiguu și implementat în grabă, scopul acestui proiect este să acopere marea majoritate a cazurilor de folosire fără să intre în scenarii de nișă. Contribuțiile sunt binevenite.
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
Deoarece **TCPDF** este o librărie complexă și presupune o oarecare experiență în folosire pentru rezultate optime, am creat o serie de instrucțiuni de bază (text, linie, dreptunghi etc) care se utilizează în generarea documentului. Drept urmare nu este necesară învățarea **TCPDF** pentru personalizarea documentelor.

### Factură PDF generată pe mfinante.ro
![anaf](https://github.com/user-attachments/assets/fe07d762-477f-4a77-9f05-fc1699f2faba)

### Factură PDF generată de xml2pdf.php
![xml2pdf](https://github.com/user-attachments/assets/efe646bf-8ec4-4598-a3a0-457391db4dff)
