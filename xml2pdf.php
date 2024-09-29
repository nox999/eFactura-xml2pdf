<?php

  /*
    xml2pdf.php - PHP library that converts the XML eFactură invoices to the PDF format.

    Copyright (C) 2024

    This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
    You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
  */

  function xml2pdfParse($xmlString) { // parcurge un XML eFactură și extrage câmpurile relevante într-un array
    $judete=array(
      'AB'=>'Alba','AR'=>'Arad','AG'=>'Arges','BC'=>'Bacau','BH'=>'Bihor','BN'=>'Bistrita-Nasaud','BT'=>'Botosani','BR'=>'Braila','BV'=>'Brasov','B'=>'Bucuresti','BZ'=>'Buzau','CL'=>'Calarasi',
      'CS'=>'Caras-Severin','CJ'=>'Cluj','CT'=>'Constanta','CV'=>'Covasna','DB'=>'Dambovita','DJ'=>'Dolj','GL'=>'Galati','GR'=>'Giurgiu','GJ'=>'Gorj','HR'=>'Harghita','HD'=>'Hunedoara','IL'=>'Ialomita',
      'IS'=>'Iasi','IF'=>'Ilfov','MM'=>'Maramures','MH'=>'Mehedinti','MS'=>'Mures','NT'=>'Neamt','OT'=>'Olt','PH'=>'Prahova','SJ'=>'Salaj','SM'=>'Satu Mare','SB'=>'Sibiu','SV'=>'Suceava',
      'TR'=>'Teleorman','TM'=>'Timis','TL'=>'Tulcea','VL'=>'Valcea','VS'=>'Vaslui','VN'=>'Vrancea'
    );

    $xmlString=preg_replace('/( (xmlns|xsi)(:[a-zA-Z0-9]+){0,1}="[^"]+")/','',$xmlString);
    $xmlString=str_replace(array('<cbc:','</cbc:','<cac:','</cac:','<ubl:','</ubl:'),array('<','</','<','</','<','</'),$xmlString);
    $xml=simplexml_load_string($xmlString);

    if ($xml!==false) {
      $dataFactura=$xml->xpath('//IssueDate');
      if (count($dataFactura)) {
        $dataFactura=(string)$dataFactura[0][0];
        if (preg_match('/^[0-9]{4}\-[0-9]{2}-[0-9]{2}$/',$dataFactura)) {
          list($y,$m,$d)=explode('-',$dataFactura);
          $numar=(string)$xml->xpath('//ID')[0][0];

          $factura=array(
            'nume'=>(string)$xml->xpath('//AccountingCustomerParty/Party/PartyLegalEntity/RegistrationName')[0][0],
            'nrRegCom'=>count($xml->xpath('//AccountingCustomerParty/Party/PartyLegalEntity/CompanyID'))?str_replace(' ','',(string)$xml->xpath('//AccountingCustomerParty/Party/PartyLegalEntity/CompanyID')[0][0]):'-',
            'CIF'=>count($xml->xpath('//AccountingCustomerParty/Party/PartyTaxScheme/CompanyID'))?str_replace(' ','',(string)$xml->xpath('//AccountingCustomerParty/Party/PartyTaxScheme/CompanyID')[0][0]):'-',
            'adresa'=>(string)$xml->xpath('//AccountingCustomerParty/Party/PostalAddress/StreetName')[0][0],
            'localitate'=>preg_replace('/(SECTOR)([0-9]{1})/','Sector \\2',(string)$xml->xpath('//AccountingCustomerParty/Party/PostalAddress/CityName')[0][0]),
            'judet'=>$judete[preg_replace('/^(RO-)/','',(string)$xml->xpath('//AccountingCustomerParty/Party/PostalAddress/CountrySubentity')[0][0])],
          );

          if (preg_match('/^[A-Z]{0,2}[0-9]+$/',$factura['nrRegCom'])) {
            list($factura['nrRegCom'],$factura['CIF'])=array($factura['CIF'],$factura['nrRegCom']);
          }

          $factura['dataFactura']=$dataFactura;
          $factura['numar']=$numar;
          $factura['tip']=(string)$xml->xpath('//InvoiceTypeCode')[0][0];
          $factura['instrumentPlata']=count($xml->xpath('//PaymentMeans/PaymentMeansCode'))?(string)$xml->xpath('//PaymentMeans/PaymentMeansCode')[0][0]:'-';
          $factura['nota']=count($xml->xpath('//Note'))?(string)$xml->xpath('//Note')[0][0]:'';

          $factura['TVA']=array();
          $factura['totalTVA']=0;
          $factura['totalFaraTVA']=0;
          $totaluri=$xml->xpath('//TaxTotal/TaxSubtotal');
          foreach($totaluri as $total) {
            $factura['totalTVA']+=(float)$total->TaxAmount;
            $factura['totalFaraTVA']+=(float)$total->TaxableAmount;
            $factura['TVA'][(float)$total->TaxCategory->Percent]=array(
              'totalTVA'=>(float)$total->TaxAmount,
              'totalFaraTVA'=>(float)$total->TaxableAmount,
            );
          }
          $factura['total']=$factura['totalTVA']+$factura['totalFaraTVA'];

          $factura['firmaNume']=(string)$xml->xpath('//AccountingSupplierParty/Party/PartyLegalEntity/RegistrationName')[0][0];
          $factura['firmaNrRegCom']=str_replace(' ','',(string)$xml->xpath('//AccountingSupplierParty/Party/PartyLegalEntity/CompanyID')[0][0]);
          $factura['firmaCIF']=(string)$xml->xpath('//AccountingSupplierParty/Party/PartyTaxScheme/CompanyID')[0][0];
          $factura['firmaAdresa']=(string)$xml->xpath('//AccountingSupplierParty/Party/PostalAddress/StreetName')[0][0];
          $factura['firmaLocalitate']=preg_replace('/(SECTOR)([0-9]{1})/','Sector \\2',(string)$xml->xpath('//AccountingSupplierParty/Party/PostalAddress/CityName')[0][0]);
          $factura['firmaJudet']=$judete[preg_replace('/^(RO-)/','',(string)$xml->xpath('//AccountingSupplierParty/Party/PostalAddress/CountrySubentity')[0][0])];
          $factura['firmaCont']=count($xml->xpath('//PaymentMeans/PayeeFinancialAccount/ID'))?(string)$xml->xpath('//PaymentMeans/PayeeFinancialAccount/ID')[0][0]:'';
          $factura['firmaContNume']=count($xml->xpath('//PaymentMeans/PayeeFinancialAccount/Name'))?(string)$xml->xpath('//PaymentMeans/PayeeFinancialAccount/Name')[0][0]:'';
          $factura['firmaBanca']=count($xml->xpath('//PaymentMeans/PayeeFinancialAccount/FinancialInstitutionBranch/ID'))?(string)$xml->xpath('//PaymentMeans/PayeeFinancialAccount/FinancialInstitutionBranch/ID')[0][0]:'';

          $factura['produse']=array();
          $lines=$xml->xpath('//InvoiceLine');
          foreach($lines as $line) {
            $factura['produse'][]=array(
              'produs'=>(string)$line->Item->Name,
              'pretFaraTVA'=>(float)$line->Price->PriceAmount,
              'moneda'=>(string)$line->Price->PriceAmount->attributes()->currencyID,
              'cantitate'=>(int)$line->InvoicedQuantity,
              'um'=>(string)$line->InvoicedQuantity->attributes()->unitCode,
              'TVA'=>(float)$line->Item->ClassifiedTaxCategory->Percent,
              'totalFaraTVA'=>(float)$line->LineExtensionAmount,
            );
          }

          return $factura;
        } else {
          // Error: Invalid date
          return false;
        }
      } else {
        // Error: Date not found
        return false;
      }
    } else {
      // Error: Invalid XML
      return false;
    }
  }

  function xml2pdfEvalExpression($y,$vars) { // evaluează expresii în cadrul funcțiilor de desenare
    if (gettype($y)=='string') {
      foreach($vars as $p=>$val) {
        if (gettype($val)!='array') {
          $y=str_replace($p,$val,$y);
        }
      }
      $y=eval("return {$y};");
    }

    return $y;
  }

  function xml2pdfDraw($t,$pdf,&$vars) { // funcții de desenare folosite pentru reprezentarea documentului
    switch($t[0]) {
      case '_newPage': // '_newPage', array(...) (definește ce se întâmplă când se adaugă o pagină nouă)
        $vars['_newPage']=$t[1];
        break;
      case 'newPageIf': // 'newPageIf', y (poziția curentă > y)[, array(text, width, size, bold)] (dacă poziția curentă e mai mare de "y" se trece la o pagină nouă, alternativ verifică dacă poziția curentă + înălțimea textului depășește y)
        $isNewPage=false;
        if (!isset($t[2]) && $pdf->getY()>=$t[1]) {
          $isNewPage=true;
        } elseif (isset($t[2])) {
          $pdf->SetFont('freesans',(!is_null($t[2][3]) && $t[2][3])?'B':'',!is_null($t[2][2])?$t[2][2]:9);
          $cellHeight=$pdf->GetStringHeight($t[2][1],$t[2][0]);
          if ($pdf->getY()+$cellHeight>=$t[1]) {
            $isNewPage=true;
          }
        }

        if ($isNewPage) {
          if (!isset($vars['_page'])) {
            $vars['_page']=1;
          }
          $vars['_page']++;
          $pdf->addPage();
          if (isset($vars['_newPage'])) {
            foreach ($vars['_newPage'] as $tNP) {
              xml2pdfDraw($tNP,$pdf,$vars);
            }
          }
        }
        break;
      case 'setY': // 'setY', value (mută poziția y curentă)
        $y=xml2pdfEvalExpression($t[1],$vars);
        $pdf->setXY($pdf->getX(),$y);
        break;
      case 'getY': // 'getY', var (salvează pozița y curentă)
        $vars[$t[1]]=$pdf->getY();
        break;
      case 'moveY': // 'moveY', value (mută poziția y curentă)
        $pdf->setXY($pdf->getX(),$pdf->getY()+$t[1]);
        break;
      case 'setVar': // 'setVar', var, value (definește o variabilă)
        $vars[$t[1]]=$t[2];
        break;
      case 'line': // 'line', x1, y2, x2, y2 (desenează o linie)
        $pdf->line($t[1],xml2pdfEvalExpression($t[2],$vars),$t[3],xml2pdfEvalExpression($t[4],$vars),array('width'=>0.2,'color'=>array(0,0,0,100)));
        break;
      case 'rect': // 'rect', x1, y1, x2, y2[, fill-color CMYK] (desenează un dreptunghi, opțional umplut cu o culoare)
        $y1=xml2pdfEvalExpression($t[2],$vars);
        $y2=xml2pdfEvalExpression($t[4],$vars);
        $pdf->rect($t[1],$y1,$t[3]-$t[1],$y2-$y1,isset($t[5])?'DF':'',isset($t[5])?array('0000'=>array()):array('LRTB'=>array('width'=>0.2,'color'=>array(0,0,0,100))),isset($t[5])?$t[5]:array());
        break;
      case 'text': // 'text', text, x, y, size, bold, array(x2,alignH,alignV='B'), isNextLine (desenează un text, dacă x sau y sunt false se folosește poziția curentă, dacă x2 este definit se folosește pentru centrarea textului, alignH poate fi 'L','C','R','J', dacă alignV = 'B' atunci textul se desenează de la poziția curentă în sus, dacă isNextLine este true atunci poziția curentă pe y se mută după text, altfel se mută orizontal după text)
        list(,$text,$x,$y,$size,$isBold,$align,$isNextLine)=array_pad($t,8,null);

        $pdf->SetFont('freesans',(!is_null($isBold) && $isBold)?'B':'',!is_null($size)?$size:9);
        if ($y!==false) {
          $pdf->SetY(xml2pdfEvalExpression($y,$vars));
        }
        if ($x!==false) {
          $pdf->SetX($x);
        }

        $yBefore=$pdf->getY();
        $cellWidth=$pdf->GetStringWidth($text)+0.1;
        if (gettype($align)=='array' && isset($align[2]) && $align[2]=='B') {
          $cellHeight=$pdf->GetStringHeight($align[0]-$x,$text);
          $pdf->setXY($pdf->getX(),$yBefore-$cellHeight);
        }

        $pdf->MultiCell(
          gettype($align)=='array'?$align[0]-$x:$cellWidth, // cell width
          0, // cell minimum height
          $text,
          0, // no border
          gettype($align)=='array'?$align[1]:'L', // horizontal align
          0, // no fill
          (is_null($isNextLine) || !$isNextLine)?0:1 // mută poziția la dreapta/sus sau jos la începutul următoarei linii
        );

        if (is_null($isNextLine) || !$isNextLine) {
          $pdf->setXY($pdf->getX(),$yBefore);
        }

        break;
    }
  }

  function xml2pdfRender($factura,$return=false) { // generează document PDF pornind de la un array factură

    // cele mai comune tipuri de documente, instrumente de plată și unități de măsură, la restul se afișează doar codul
    $tipuriDocument=array('-'=>'Nedefinit','380'=>'Factură','751'=>'Factură - informații în scopuri contabile');
    $instrumentePlata=array('1'=>'Nespecificat','10'=>'Numerar','42'=>'Ordin de plată','48'=>'Card bancar','54'=>'Card de credit','55'=>'Card de debit','68'=>'Plata online','ZZZ'=>'Instrument agreat');
    $unitati=array(
      'C62'=>'unitate',
      'EA'=>'unitate',
      'GRM'=>'gr.',
      'H87'=>'buc.',
      'HUR'=>'oră',
      'KGM'=>'kg.',
      'KWH'=>'kw. oră',
      'M4'=>'val. monetară',
      'MON'=>'lună',
      'MTQ'=>'metru cub',
      'MTR'=>'metru',
      'SET'=>'set',
      'XBE'=>'pachet',
      'XPP'=>'buc.',
    );

    $pdf=new TCPDF('P','mm','A4',true,'UTF-8',false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('xml2pdf.php');
    $pdf->SetTitle('Factura '.$factura['firmaNume'].' din '.$factura['dataFactura']);

    // setup doc

    $pdf->SetMargins(0,0,0);
    $pdf->setCellPadding(0);
    $pdf->setImageScale(1);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false,0);

    // setup page

    $pdf->AddPage();

    $draw=array(
      array('_newPage',array(
        array('rect',5,5,205,292),

        array('text','Linia',10,10,8,true),
        array('text','Nume articol',20,10,8,true),
        array('text','Preț net',95,10,8,true),
        array('text','Moneda',115,10,8,true),
        array('text','Cantitate',130,10,8,true),
        array('text','U.M.',145,10,8,true),
        array('text','Cota TVA',165,10,8,true),
        array('text','Valoare netă',180,10,8,true),

        array('line',10,15.5,200,15.5),

        array('setY',17.5),
        array('setVar','deltaY',-2.5),
      )),
      array('rect',5,5,205,292),

      // header

      array('text',"FACTURA ",10,10,12,true,null,false),
      array('text',"{$factura['numar']} / {$factura['dataFactura']}",false,false,12,false),
      array('text',"Tip document: {$factura['tip']}".(isset($tipuriDocument[$factura['tip']])?" ({$tipuriDocument[$factura['tip']]})":''),105,10,8,false,array(200,'R'),true),
      array('text',"Instrument plată: {$factura['instrumentPlata']}".(isset($instrumentePlata[$factura['instrumentPlata']])?" ({$instrumentePlata[$factura['instrumentPlata']]})":''),105,false,8,false,array(200,'R')),

      // vânzător

      array('text','VÂNZĂTOR',15,22.5,10,true,array(97.5,'C')),

      array('text','Nume',12.5,30,8,true),
      array('text',$factura['firmaNume'],35,false,8,false,array(100,'L'),true),

      array('text','Nr. Reg. Com.',12.5,false,8,true),
      array('text',$factura['firmaNrRegCom'],35,false,8,false,array(100,'L'),true),

      array('text','CIF',12.5,false,8,true),
      array('text',$factura['firmaCIF'],35,false,8,false,array(100,'L'),true),

      array('text','Adresă',12.5,false,8,true),
      array('text',$factura['firmaAdresa'],35,false,8,false,array(100,'L'),true),

      array('text','Localitate',12.5,false,8,true),
      array('text',$factura['firmaLocalitate'],35,false,8,false,array(100,'L'),true),

      array('text','Județ',12.5,false,8,true),
      array('text',$factura['firmaJudet'],35,false,8,false,array(100,'L'),true),

      array('text','Cont',12.5,false,8,true),
      array('text',$factura['firmaCont']?$factura['firmaCont']:'-',35,false,8,false,array(100,'L'),true),

      array('text','Cont (nume)',12.5,false,8,true),
      array('text',$factura['firmaContNume']?$factura['firmaContNume']:'-',35,false,8,false,array(100,'L'),true),

      array('text','Banca',12.5,false,8,true),
      array('text',$factura['firmaBanca']?$factura['firmaBanca']:'-',35,false,8,false,array(100,'L'),true),

      array('getY','vanzator'),

      array('rect',10,20,102.5,'vanzator+2.5'),

      // cumpărător

      array('text','CUMPĂRĂTOR',112.5,22.5,10,true,array(195,'C')),

      array('text','Nume',110,30,8,true),
      array('text',$factura['nume'],132.5,false,8,false,array(197.5,'L'),true),

      array('text','Nr. Reg. Com.',110,false,8,true),
      array('text',$factura['nrRegCom'],132.5,false,8,false,array(197.5,'L'),true),

      array('text','CIF',110,false,8,true),
      array('text',$factura['CIF'],132.5,false,8,false,array(197.5,'L'),true),

      array('text','Adresă',110,false,8,true),
      array('text',$factura['adresa'],132.5,false,8,false,array(197.5,'L'),true),

      array('text','Localitate',110,false,8,true),
      array('text',$factura['localitate'],132.5,false,8,false,array(197.5,'L'),true),

      array('text','Județ',110,false,8,true),
      array('text',$factura['judet'],132.5,false,8,false,array(197.5,'L'),true),

      array('rect',107.5,20,200,'vanzator+2.5'),

      // header listă produse

      array('text','Linia',10,'vanzator+7.5',8,true),
      array('text','Nume articol',20,'vanzator+7.5',8,true),
      array('text','Preț net',95,'vanzator+7.5',8,true),
      array('text','Moneda',115,'vanzator+7.5',8,true),
      array('text','Cantitate',130,'vanzator+7.5',8,true),
      array('text','U.M.',145,'vanzator+7.5',8,true),
      array('text','Cota TVA',165,'vanzator+7.5',8,true),
      array('text','Valoare netă',180,'vanzator+7.5',8,true),

      array('line',10,'vanzator+13',200,'vanzator+13'),
    );

    $draw[]=array('setVar','deltaY',0);

    // listă produse

    foreach($factura['produse'] as $i=>$p) {
      $y=$i?false:'vanzator+15';
      $draw[]=array('newPageIf',285,array($p['produs'],70,8,false));
      $draw[]=array('text',$i+1,10,$y,8,false);
      $draw[]=array('text',$p['pretFaraTVA'],95,$y,8,false);
      $draw[]=array('text',$p['moneda'],115,$y,8,false);
      $draw[]=array('text',$p['cantitate'],130,$y,8,false);
      $draw[]=array('text',$p['um'].(isset($unitati[$p['um']])?" ({$unitati[$p['um']]})":''),145,$y,8,false);
      $draw[]=array('text',$p['TVA'].'%',165,$y,8,false);
      $draw[]=array('text',$p['totalFaraTVA'],180,$y,8,false);
      $draw[]=array('text',$p['produs'],20,$y,8,false,array(90,'L'),true);
      $draw[]=array('moveY','1');
    }

    // este nevoie să trecem pe pagina următoare pentru a desena totalurile?

    $draw[]=array('_newPage',array(
      array('rect',5,5,205,292),
      array('setVar','deltaY',-2.5),
    ));
    $draw[]=array('newPageIf',230);

    // TVA și total

    if ($factura['totalTVA']!=0) {
      $draw[]=array('rect',160,'deltaY+257',200,'deltaY+287');
      $draw[]=array('text','TOTAL',162.5,'deltaY+266.5',10,true,array(197.5,'C'),true);
      $draw[]=array('text',$factura['total'],162.5,false,12,true,array(197.5,'C'));

      $count=0;
      if (count($factura['TVA'])>1) {
        $factura['TVA']['TOTAL']=array(
          'totalTVA'=>$factura['totalTVA'],
          'totalFaraTVA'=>$factura['totalFaraTVA'],
        );
      }
      foreach($factura['TVA'] as $i=>$t) {
        $x=105-count($factura['TVA'])*25/2+$count*25;
        $draw[]=array('text','TVA '.($i=='TOTAL'?$i:"{$i}%"),$x,'deltaY+262',8,true,array($x+25,'C'),true);
        $draw[]=array('text',$t['totalTVA'],$x,false,12,false,array($x+25,'C'),true);
        $draw[]=array('text','Bază calcul',$x,false,8,true,array($x+25,'C'),true);
        $draw[]=array('text',$t['totalFaraTVA'],$x,false,12,false,array($x+25,'C'));
        $count++;
      }

      $draw[]=array('rect',10,'deltaY+257',50,'deltaY+287');
      $draw[]=array('text','TOTAL',12.5,'deltaY+264.5',10,true,array(47.5,'C'),true);
      $draw[]=array('text','fără TVA',12.5,false,8,false,array(47.5,'C'),true);
      $draw[]=array('text',$factura['totalFaraTVA'],12.5,false,12,true,array(47.5,'C'));

      $draw[]=array('rect',55,'deltaY+257',155,'deltaY+287');
    } else {
      $draw[]=array('rect',10,'deltaY+257',200,'deltaY+287');
      $draw[]=array('text','TOTAL',5,'deltaY+266.5',10,true,array(205,'C'),true);
      $draw[]=array('text',$factura['total'],5,false,12,true,array(205,'C'));
    }

    $draw[]=array('text',$factura['nota'],10,252,8,false,array(200,'L','B'));

    $vars=array(
      '_page'=>1,
    );

    foreach($draw as $t) {
      xml2pdfDraw($t,$pdf,$vars);
    }

    // dacă există mai mult de o pagină, desenează numere

    if (isset($vars['_page']) && $vars['_page']>1) {
      for ($i=1; $i<=$vars['_page']; $i++) {
        $pdf->setPage($i);
        xml2pdfDraw(array('text',"Pagina {$i} / {$vars['_page']}",5,290,8,false,array(205,'C','B')),$pdf,$vars);
      }
    }

    // întoarce PDF-ul, sau îl trimite inline în browser

    if ($return) {
      return $pdf->Output('factura-'.$factura['dataFactura'].'.pdf','S');
    } else {
      $pdf->Output('factura-'.$factura['dataFactura'].'.pdf','I');
    }
  }

?>
