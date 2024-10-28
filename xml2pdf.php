<?php

  /*
    xml2pdf.php - PHP library that converts the XML eFactură invoices to the PDF format.

    Copyright (C) 2024

    This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
    You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
  */

  require_once('xml2pdfDraw.php');
  require_once('xml2pdfParse.php');

  function xml2pdfRender($factura,$return=false,$titlu='factura-{{data}}') { // generează instrucțiunile de desenare pornind de la un array factură
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
    if (file_exists(dirname(__FILE__).'/logo/'.preg_replace('/[^0-9]+/','',$factura['firmaCIF']).'.png')) {
      $draw[]=array('image',dirname(__FILE__).'/logo/'.preg_replace('/[^0-9]+/','',$factura['firmaCIF']).'.png',10,10,12);
      $draw[]=array('moveX',2.5);
      $hasLogo=true;
    };

    $draw[]=array('text',"FACTURA ",$hasLogo?false:10,$hasLogo?false:10,12,true,array(0,'L',$hasLogo?'M':'',12),false);
    $draw[]=array('text',"{$factura['numar']} / {$factura['dataFactura']}",false,false,12,false,array(0,'L',$hasLogo?'M':'',12));
    $draw[]=array(
      'text',
      "Tip document: {$factura['tip']}".(isset($factura['tipText'])?" ({$factura['tipText']})":'')."\n".
      "Instrument plată: {$factura['instrumentPlata']}".(isset($factura['instrumentPlataText'])?" ({$factura['instrumentPlataText']})":''),
      105,10,8,false,array(200,'R',$hasLogo?'M':'',12),true
    );
    $draw[]=array('setVar','deltaY',$hasLogo?7:0);

    // vânzător

    $draw[]=array('text','VÂNZĂTOR',15,'{{deltaY}}+22.5',10,true,array(97.5,'C'));

    $draw[]=array('text','Nume',12.5,'{{deltaY}}+30',8,true);
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

    $draw[]=array('rect',10,'{{deltaY}}+20',102.5,'{{vanzator}}+2.5');

    // cumpărător

    $draw[]=array('text','CUMPĂRĂTOR',112.5,'{{deltaY}}+22.5',10,true,array(195,'C'));

    $draw[]=array('text','Nume',110,'{{deltaY}}+30',8,true);
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

    $draw[]=array('rect',107.5,'{{deltaY}}+20',200,'{{vanzator}}+2.5');

    // antet listă produse

    $draw[]=array('text','Linia',10,'{{vanzator}}+7.5',8,true);
    $draw[]=array('text','Nume articol',20,'{{vanzator}}+7.5',8,true);
    $draw[]=array('text','Preț net',95,'{{vanzator}}+7.5',8,true);
    $draw[]=array('text','Moneda',115,'{{vanzator}}+7.5',8,true);
    $draw[]=array('text','Cantitate',130,'{{vanzator}}+7.5',8,true);
    $draw[]=array('text','U.M.',145,'{{vanzator}}+7.5',8,true);
    $draw[]=array('text','Cota TVA',165,'{{vanzator}}+7.5',8,true);
    $draw[]=array('text','Valoare netă',180,'{{vanzator}}+7.5',8,true);

    $draw[]=array('line',10,'{{vanzator}}+13',200,'{{vanzator}}+13');

    $draw[]=array('setVar','deltaY',0);

    // listă produse

    foreach($factura['produse'] as $i=>$p) {
      $y=$i?false:'{{vanzator}}+15';
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
      $draw[]=array('text',$p['um'].(isset($p['umText'])?" ({$p['umText']})":''),145,$y,8,false);
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
    if (isset($factura['nota'])) {
      $draw[]=array('newPageIf',252,array($factura['nota'],200,8,false));
    } else {
      $draw[]=array('newPageIf',252);
    }

    // TVA și total

    if ($factura['totalTVA']!=0) {
      $draw[]=array('rect',160,'{{deltaY}}+257',200,'{{deltaY}}+287');
      $draw[]=array('text','TOTAL',162.5,'{{deltaY}}+266.5',10,true,array(197.5,'C'),true);
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
        $draw[]=array('text','TVA '.($i=='TOTAL'?$i:"{$i}%"),$x,'{{deltaY}}+262',8,true,array($x+25,'C'),true);
        $draw[]=array('text',$t['totalTVA'],$x,false,12,false,array($x+25,'C'),true);
        $draw[]=array('text','Bază calcul',$x,false,8,true,array($x+25,'C'),true);
        $draw[]=array('text',$t['totalFaraTVA'],$x,false,12,false,array($x+25,'C'));
        $count++;
      }

      $draw[]=array('rect',10,'{{deltaY}}+257',50,'{{deltaY}}+287');
      $draw[]=array('text','TOTAL',12.5,'{{deltaY}}+264.5',10,true,array(47.5,'C'),true);
      $draw[]=array('text','fără TVA',12.5,false,8,false,array(47.5,'C'),true);
      $draw[]=array('text',$factura['totalFaraTVA'],12.5,false,12,true,array(47.5,'C'));

      $draw[]=array('rect',55,'{{deltaY}}+257',155,'{{deltaY}}+287');
    } else {
      $draw[]=array('rect',10,'{{deltaY}}+257',200,'{{deltaY}}+287');
      $draw[]=array('text','TOTAL',5,'{{deltaY}}+266.5',10,true,array(205,'C'),true);
      $draw[]=array('text',$factura['total'],5,false,12,true,array(205,'C'));
    }

    if (isset($factura['nota'])) {
      $draw[]=array('text',$factura['nota'],10,252,8,false,array(200,'L','B'));
    }

    if (isset($factura['fisiere']) && count($factura['fisiere'])) {
      $draw[]=array('attachments',$factura['fisiere']);
    }

    // construiește numele documentului

    $filename=preg_replace('/[<>:"\/\\\|\?\*]/','',trim(str_replace(
      array('{{data}}','{{numar}}','{{furnizor}}'),
      array($factura['dataFactura'],$factura['numar'],$factura['firmaNume']),
      $titlu
    ))).'.pdf';

    // procesează instrucțiunile de desenare

    xml2pdfDraw($draw,'Factura '.$factura['firmaNume'].' din '.$factura['dataFactura'],$filename,$return);
  }

?>
