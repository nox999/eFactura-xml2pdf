# eFactura-xml2pdf
Librărie PHP care transformă documentele eFactură din format XML în format PDF pentru tipărire și vizualizare.
## De ce există?
Ministerul de Finanțe pune la dispoziție o ["aplicație"](https://www.anaf.ro/uploadxml/) care transformă facturile din XML în PDF (serviciu disponibil și prin API). Din păcate rezultatele pe care le produce sunt aproape ilizibile și complet deconectate de ce ar avea nevoie o companie reală - un obstacol inutil pentru cei care vor să-și facă singuri implementarea eFactură. Pentru că standardul este stufos, redundant, prost documentat, ambiguu și implementat în grabă, scopul acestui proiect este să acopere marea majoritate a cazurilor de folosire fără să intre în scenarii de nișă.
## De ce să-l folosești?
* Generează fișiere PDF lizibile;
* Nu depinde de disponibilitatea serverelor ANAF;
* Ușor de personalizat;
* Simplu și flexibil (tot codul este procedural și comentat);
* Nu se folosesc alte librării externe în afară de [TCPDF](https://github.com/tecnickcom/tcpdf). Dacă se dorește atașarea anexelor în format PDF incluse în XML este necesară și includerea [FPDI](https://github.com/Setasign/FPDI);
* Compatibil cu PHP 5-8;
* Publicat sub licența [GPLv3](https://www.gnu.org/licenses/gpl-3.0.en.html). Contribuțiile sunt binevenite;
## Cum se folosește?
```php
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
```
## Cum arată documentele generate?
Se poate folosi template-ul existent sau se pot personaliza documentele cu ușurință, fără a fi necesară învățarea librăriei **TCPDF**.

### Factură PDF generată pe anaf.ro/uploadxml
![anaf](https://github.com/user-attachments/assets/64415533-7cd2-4152-9a45-5559fdaedf3f)

### Factură PDF generată de xml2pdf.php
![xml2pdf](https://github.com/user-attachments/assets/8bbdb51f-dbd4-4598-9607-115957f78b1a)

