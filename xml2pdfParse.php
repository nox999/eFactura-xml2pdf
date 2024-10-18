<?php

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

          $factura['fisiere']=array();
          $files=$xml->xpath('//EmbeddedDocumentBinaryObject');
          foreach($files as $file) {
            if ((string)$file['mimeCode']==='application/pdf') {
              $factura['fisiere'][]=base64_decode($file[0]);
            }
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

?>
