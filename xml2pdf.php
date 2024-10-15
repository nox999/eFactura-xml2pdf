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
          if (count($xml->xpath('/Invoice/Note'))) {
            $factura['nota']='';
            for ($i=0; $i<count($xml->xpath('/Invoice/Note')); $i++) {
              $factura['nota'].=($factura['nota']?"\n":'').trim((string)$xml->xpath('/Invoice/Note')[$i][0]);
            }
          }

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
          $factura['firmaNrRegCom']=count($xml->xpath('//AccountingSupplierParty/Party/PartyLegalEntity/CompanyID'))?str_replace(' ','',(string)$xml->xpath('//AccountingSupplierParty/Party/PartyLegalEntity/CompanyID')[0][0]):'';
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
              'produs'=>trim((string)$line->Item->Name),
              'descriere'=>trim((string)$line->Item->Description),
              'nota'=>trim((string)$line->Note),
              'codVanzator'=>trim((string)$line->Item->SellersItemIdentification->ID),
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
      case '_newPageBefore': // '_newPageBefore', array(...) (definește ce se întâmplă înainte să se adauge o pagină nouă)
        $vars['_newPageBefore']=$t[1];
        break;
      case '_newPageAfter': // '_newPageAfter', array(...) (definește ce se întâmplă după ce se adaugă o pagină nouă)
        $vars['_newPageAfter']=$t[1];
        break;
      case 'newPageIf': // 'newPageIf', y (poziția curentă > y)[, array(text, width, size, bold), array ...] (dacă poziția curentă e mai mare de "y" se trece la o pagină nouă, alternativ verifică dacă poziția curentă + înălțimea textului (textelor) depășește y)
        $isNewPage=false;
        if (!isset($t[2]) && $pdf->getY()>=$t[1]) {
          $isNewPage=true;
        } elseif (isset($t[2])) {
          $simulatedHeight=$pdf->getY();

          for ($i=2; $i<count($t); $i++) {
            $pdf->SetFont('freesans',(!is_null($t[$i][3]) && $t[$i][3])?'B':'',!is_null($t[$i][2])?$t[$i][2]:9);
            $cellHeight=$pdf->GetStringHeight($t[2][1],$t[2][0]);
            $simulatedHeight+=$cellHeight;
          }

          if ($simulatedHeight>=$t[1]) {
            $isNewPage=true;
          }
        }

        if ($isNewPage) {
          if (!isset($vars['_page'])) {
            $vars['_page']=1;
          }
          if (isset($vars['_newPageBefore'])) {
            foreach ($vars['_newPageBefore'] as $tNP) {
              xml2pdfDraw($tNP,$pdf,$vars);
            }
          }
          $vars['_page']++;
          $pdf->addPage();
          if (isset($vars['_newPageAfter'])) {
            foreach ($vars['_newPageAfter'] as $tNP) {
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
        case 'moveX': // 'moveX', value (mută poziția x curentă)
          $pdf->setXY($pdf->getX()+$t[1],$pdf->getY());
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
      case 'text': // 'text', text, x, y, size, bold, array(x2, alignH, alignV='B/M', y2), isNextLine (desenează un text, dacă x sau y sunt false se folosește poziția curentă, dacă x2 este definit se folosește pentru centrarea textului, alignH poate fi 'L','C','R','J', dacă alignV = 'B' atunci textul se desenează de la poziția curentă în sus, dacă isNextLine este true atunci poziția curentă pe y se mută după text, altfel se mută orizontal după text)
        list(,$text,$x,$y,$size,$isBold,$align,$isNextLine)=array_pad($t,8,null);

        foreach($vars as $p=>$val) {
          if (gettype($val)!=='array') {
            $text=str_replace('{{'.$p.'}}',(string)$val,$text);
          }
        }

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
          $cellHeight=$pdf->GetStringHeight($align[0]?$align[0]-$x:0,$text);
          $pdf->setXY($pdf->getX(),$yBefore-$cellHeight);
        }

        if (gettype($align)=='array' && isset($align[2]) && $align[2]=='M' && isset($align[3])) {
          $cellHeight=$pdf->GetStringHeight($align[0]?$align[0]-$x:0,$text);
          $pdf->setXY($pdf->getX(),$yBefore+($align[3]-$cellHeight)/2);
        }

        $pdf->MultiCell(
          gettype($align)=='array' && $align[0]?$align[0]-$x:$cellWidth, // cell width
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
      case 'image': // 'image', file, x, y, h
        $pdf->image($t[1],$t[2],$t[3],0,$t[4],'PNG','','T');
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

    // inițializează document

    $pdf->SetMargins(0,0,0);
    $pdf->setCellPadding(0);
    $pdf->setImageScale(1);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false,0);

    // creează prima pagină

    $pdf->AddPage();

    // instrucțiuni de desenare

    $draw=array();

    $draw[]=array('_newPageBefore',array(
      array('text',"Pagina {{_page}}",5,290,8,false,array(205,'C','B')),
    ));
    $draw[]=array('_newPageAfter',array(
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

      array('text',"Pagina {{_page}}",5,290,8,false,array(205,'C','B')),
      array('setY',17.5),
      array('setVar','deltaY',-2.5),
      array('_newPageBefore',array()),
    ));

    $draw[]=array('rect',5,5,205,292);

    // antet

    $hasLogo=false;
    if (file_exists('logo/'.preg_replace('/[^0-9]+/','',$factura['firmaCIF']).'.png')) {
      $draw[]=array('image','logo/'.preg_replace('/[^0-9]+/','',$factura['firmaCIF']).'.png',10,10,12);
      $draw[]=array('moveX',2.5);
      $hasLogo=true;
    };

    $draw[]=array('text',"FACTURA ",$hasLogo?false:10,$hasLogo?false:10,12,true,array(0,'L',$hasLogo?'M':'',12),false);
    $draw[]=array('text',"{$factura['numar']} / {$factura['dataFactura']}",false,false,12,false,array(0,'L',$hasLogo?'M':'',12));
    $draw[]=array(
      'text',
      "Tip document: {$factura['tip']}".(isset($tipuriDocument[$factura['tip']])?" ({$tipuriDocument[$factura['tip']]})":'')."\n".
      "Instrument plată: {$factura['instrumentPlata']}".(isset($instrumentePlata[$factura['instrumentPlata']])?" ({$instrumentePlata[$factura['instrumentPlata']]})":''),
      105,10,8,false,array(200,'R',$hasLogo?'M':'',12),true
    );
    $draw[]=array('setVar','deltaY',$hasLogo?7:0);

    // vânzător

    $draw[]=array('text','VÂNZĂTOR',15,'deltaY+22.5',10,true,array(97.5,'C'));

    $draw[]=array('text','Nume',12.5,'deltaY+30',8,true);
    $draw[]=array('text',$factura['firmaNume'],35,false,8,false,array(100,'L'),true);

    $draw[]=array('text','Nr. Reg. Com.',12.5,false,8,true);
    $draw[]=array('text',$factura['firmaNrRegCom'],35,false,8,false,array(100,'L'),true);

    $draw[]=array('text','CIF',12.5,false,8,true);
    $draw[]=array('text',$factura['firmaCIF'],35,false,8,false,array(100,'L'),true);

    $draw[]=array('text','Adresă',12.5,false,8,true);
    $draw[]=array('text',$factura['firmaAdresa'],35,false,8,false,array(100,'L'),true);

    $draw[]=array('text','Localitate',12.5,false,8,true);
    $draw[]=array('text',$factura['firmaLocalitate'],35,false,8,false,array(100,'L'),true);

    $draw[]=array('text','Județ',12.5,false,8,true);
    $draw[]=array('text',$factura['firmaJudet'],35,false,8,false,array(100,'L'),true);

    $draw[]=array('text','Cont',12.5,false,8,true);
    $draw[]=array('text',$factura['firmaCont']?$factura['firmaCont']:'-',35,false,8,false,array(100,'L'),true);

    $draw[]=array('text','Cont (nume)',12.5,false,8,true);
    $draw[]=array('text',$factura['firmaContNume']?$factura['firmaContNume']:'-',35,false,8,false,array(100,'L'),true);

    $draw[]=array('text','Banca',12.5,false,8,true);
    $draw[]=array('text',$factura['firmaBanca']?$factura['firmaBanca']:'-',35,false,8,false,array(100,'L'),true);

    $draw[]=array('getY','vanzator');

    $draw[]=array('rect',10,'deltaY+20',102.5,'vanzator+2.5');

    // cumpărător

    $draw[]=array('text','CUMPĂRĂTOR',112.5,'deltaY+22.5',10,true,array(195,'C'));

    $draw[]=array('text','Nume',110,'deltaY+30',8,true);
    $draw[]=array('text',$factura['nume'],132.5,false,8,false,array(197.5,'L'),true);

    $draw[]=array('text','Nr. Reg. Com.',110,false,8,true);
    $draw[]=array('text',$factura['nrRegCom'],132.5,false,8,false,array(197.5,'L'),true);

    $draw[]=array('text','CIF',110,false,8,true);
    $draw[]=array('text',$factura['CIF'],132.5,false,8,false,array(197.5,'L'),true);

    $draw[]=array('text','Adresă',110,false,8,true);
    $draw[]=array('text',$factura['adresa'],132.5,false,8,false,array(197.5,'L'),true);

    $draw[]=array('text','Localitate',110,false,8,true);
    $draw[]=array('text',$factura['localitate'],132.5,false,8,false,array(197.5,'L'),true);

    $draw[]=array('text','Județ',110,false,8,true);
    $draw[]=array('text',$factura['judet'],132.5,false,8,false,array(197.5,'L'),true);

    $draw[]=array('rect',107.5,'deltaY+20',200,'vanzator+2.5');

    // antet listă produse

    $draw[]=array('text','Linia',10,'vanzator+7.5',8,true);
    $draw[]=array('text','Nume articol',20,'vanzator+7.5',8,true);
    $draw[]=array('text','Preț net',95,'vanzator+7.5',8,true);
    $draw[]=array('text','Moneda',115,'vanzator+7.5',8,true);
    $draw[]=array('text','Cantitate',130,'vanzator+7.5',8,true);
    $draw[]=array('text','U.M.',145,'vanzator+7.5',8,true);
    $draw[]=array('text','Cota TVA',165,'vanzator+7.5',8,true);
    $draw[]=array('text','Valoare netă',180,'vanzator+7.5',8,true);

    $draw[]=array('line',10,'vanzator+13',200,'vanzator+13');

    $draw[]=array('setVar','deltaY',0);

    // listă produse

    foreach($factura['produse'] as $i=>$p) {
      $y=$i?false:'vanzator+15';
      $produs=($p['produs'] && $p['produs']!='-'?$p['produs']:'');
      $produsInfo=$p['descriere']!=$p['produs']?($p['descriere']?($p['descriere']):''):'';
      $produsInfo.=$p['nota']!=$p['produs']?($p['nota']?(($produsInfo?"\n":'')."Notă: {$p['nota']}"):''):'';
      $produsInfo.=$p['codVanzator']?(($produsInfo?"\n":'')."Cod vânzător: {$p['codVanzator']}"):'';
      if (!$produs && $produsInfo) {
        $produs=$produsInfo;
        $produsInfo='';
      }
      if ($produsInfo) {
        $draw[]=array('newPageIf',285,array($produs,70,8,true),array($produsInfo,70,8,false));
      } else {
        $draw[]=array('newPageIf',285,array($produs,70,8,false));
      }
      $draw[]=array('text',$i+1,10,$y,8,false);
      $draw[]=array('text',$p['pretFaraTVA'],95,$y,8,false);
      $draw[]=array('text',$p['moneda'],115,$y,8,false);
      $draw[]=array('text',$p['cantitate'],130,$y,8,false);
      $draw[]=array('text',$p['um'].(isset($unitati[$p['um']])?" ({$unitati[$p['um']]})":''),145,$y,8,false);
      $draw[]=array('text',$p['TVA'].'%',165,$y,8,false);
      $draw[]=array('text',$p['totalFaraTVA'],180,$y,8,false);
      $draw[]=array('text',$produs,20,$y,8,$produsInfo?true:false,array(90,'L'),true);
      if ($produsInfo) {
        $draw[]=array('text',$produsInfo,20,false,8,false,array(90,'L'),true);
      }
      $draw[]=array('moveY','1');
    }

    // este nevoie să trecem pe pagina următoare pentru a desena totalurile?

    $draw[]=array('_newPageAfter',array(
      array('rect',5,5,205,292),
      array('setVar','deltaY',-2.5),
      array('text',"Pagina {{_page}}",5,290,8,false,array(205,'C','B')),
    ));
    if ($factura['nota']) {
      $draw[]=array('newPageIf',252,array($factura['nota'],200,8,false));
    } else {
      $draw[]=array('newPageIf',252);
    }

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

    // inițializează

    $vars=array(
      '_page'=>1,
    );

    // desenează documentul

    foreach($draw as $t) {
      xml2pdfDraw($t,$pdf,$vars);
    }

    // întoarce PDF-ul, sau îl trimite inline în browser

    if ($return) {
      return $pdf->Output('factura-'.$factura['dataFactura'].'.pdf','S');
    } else {
      $pdf->Output('factura-'.$factura['dataFactura'].'.pdf','I');
    }
  }

?>
