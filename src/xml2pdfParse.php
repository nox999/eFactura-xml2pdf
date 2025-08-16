<?php

  /*
    Copyright (C) 2025

    This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
    You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
  */

  function xml2pdfParseID($a) { // extrage CIF și J din mai multe valori
    $CIF='-';
    $nrRegCom='-';

    foreach ($a as $v) {
      $v=str_replace(' ','',trim($v));
      if (preg_match('/^(RO)?[0-9]{5,}$/',$v)) {
        $CIF=$v;
      } elseif (preg_match_all('/([JFC]{1}[0-9]{2}\/[0-9]+\/[0-9\.]+)/',$v,$matches)) { // todo detectare J nou
        $nrRegCom=$matches[0][0];
      }
    }

    return array('CIF'=>$CIF,'nrRegCom'=>$nrRegCom);
  }

  function xml2pdfParse($xmlString) { // parcurge un XML eFactură și extrage câmpurile relevante într-un array
    $judete=array(
      'AB'=>'Alba','AR'=>'Arad','AG'=>'Arges','BC'=>'Bacau','BH'=>'Bihor','BN'=>'Bistrita-Nasaud','BT'=>'Botosani','BR'=>'Braila','BV'=>'Brasov','B'=>'Bucuresti','BZ'=>'Buzau','CL'=>'Calarasi',
      'CS'=>'Caras-Severin','CJ'=>'Cluj','CT'=>'Constanta','CV'=>'Covasna','DB'=>'Dambovita','DJ'=>'Dolj','GL'=>'Galati','GR'=>'Giurgiu','GJ'=>'Gorj','HR'=>'Harghita','HD'=>'Hunedoara','IL'=>'Ialomita',
      'IS'=>'Iasi','IF'=>'Ilfov','MM'=>'Maramures','MH'=>'Mehedinti','MS'=>'Mures','NT'=>'Neamt','OT'=>'Olt','PH'=>'Prahova','SJ'=>'Salaj','SM'=>'Satu Mare','SB'=>'Sibiu','SV'=>'Suceava',
      'TR'=>'Teleorman','TM'=>'Timis','TL'=>'Tulcea','VL'=>'Valcea','VS'=>'Vaslui','VN'=>'Vrancea'
    );

    // cele mai comune tipuri de documente, instrumente de plată și unități de măsură, la restul se afișează doar codul

    $tipuriDocument=array('-'=>'Nedefinit','380'=>'Factură','381'=>'Notă de creditare','751'=>'Factură - informații în scopuri contabile');
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
    $taxeBonus=array(
      // Allowance
      '95'=>'Discount',
      // Charge
      'TV'=>'Transport',
    );

    $xmlString=preg_replace('/([ \n]{1}(xmlns|xsi)(:[a-zA-Z0-9]+){0,1}="[^"]+")/','',$xmlString);
    $xmlString=str_replace(array('<cbc:','</cbc:','<cac:','</cac:','<ubl:','</ubl:'),array('<','</','<','</','<','</'),$xmlString);
    $xml=simplexml_load_string($xmlString);

    if ($xml!==false) {
      if (count($xml->xpath('/Invoice'))) {
        $dataFactura=$xml->xpath('/Invoice/IssueDate');
        if (count($dataFactura)) {
          $dataFactura=(string)$dataFactura[0][0];
          if (preg_match('/^[0-9]{4}\-[0-9]{2}-[0-9]{2}$/',$dataFactura)) {
            list($y,$m,$d)=explode('-',$dataFactura);
            $numar=(string)$xml->xpath('/Invoice/ID')[0][0];

            $dateCompanie=xml2pdfParseID(array(
              count($xml->xpath('/Invoice/AccountingCustomerParty/Party/PartyIdentification/ID'))?(string)$xml->xpath('/Invoice/AccountingCustomerParty/Party/PartyIdentification/ID')[0][0]:'',
              count($xml->xpath('/Invoice/AccountingCustomerParty/Party/PartyLegalEntity/CompanyID'))?(string)$xml->xpath('/Invoice/AccountingCustomerParty/Party/PartyLegalEntity/CompanyID')[0][0]:'',
              count($xml->xpath('/Invoice/AccountingCustomerParty/Party/PartyTaxScheme/CompanyID'))?(string)$xml->xpath('/Invoice/AccountingCustomerParty/Party/PartyTaxScheme/CompanyID')[0][0]:'',
            ));

            $factura=array(
              'nume'=>trim((string)$xml->xpath('/Invoice/AccountingCustomerParty/Party/PartyLegalEntity/RegistrationName')[0][0]),
              'nrRegCom'=>trim($dateCompanie['nrRegCom']),
              'CIF'=>trim($dateCompanie['CIF']),
              'adresa'=>trim((string)$xml->xpath('/Invoice/AccountingCustomerParty/Party/PostalAddress/StreetName')[0][0]),
              'localitate'=>trim(preg_replace('/(SECTOR)([0-9]{1})/','Sector \\2',(string)$xml->xpath('/Invoice/AccountingCustomerParty/Party/PostalAddress/CityName')[0][0])),
              'judet'=>trim($judete[preg_replace('/^(RO-)/','',(string)$xml->xpath('/Invoice/AccountingCustomerParty/Party/PostalAddress/CountrySubentity')[0][0])]),
            );

            if (preg_match('/^[A-Z]{0,2}[0-9]+$/',$factura['nrRegCom'])) {
              list($factura['nrRegCom'],$factura['CIF'])=array($factura['CIF'],$factura['nrRegCom']);
            }

            $factura['dataFactura']=$dataFactura;
            $factura['numar']=$numar;
            $factura['tip']=(string)$xml->xpath('/Invoice/InvoiceTypeCode')[0][0];
            if (isset($tipuriDocument[$factura['tip']])) {
              $factura['tipText']=$tipuriDocument[$factura['tip']];
            }
            $factura['instrumentPlata']=count($xml->xpath('/Invoice/PaymentMeans/PaymentMeansCode'))?(string)$xml->xpath('/Invoice/PaymentMeans/PaymentMeansCode')[0][0]:'-';
            if (isset($instrumentePlata[$factura['instrumentPlata']])) {
              $factura['instrumentPlataText']=$instrumentePlata[$factura['instrumentPlata']];
            }
            if (count($xml->xpath('/Invoice/Note'))) {
              $factura['nota']='';
              for ($i=0; $i<count($xml->xpath('/Invoice/Note')); $i++) {
                $factura['nota'].=($factura['nota']?"\n":'').trim((string)$xml->xpath('/Invoice/Note')[$i][0]);
              }
            }

            $factura['TVA']=array();
            $factura['totalTVA']=0;
            $factura['totalFaraTVA']=0;
            $totaluri=$xml->xpath('/Invoice/TaxTotal/TaxSubtotal');
            foreach($totaluri as $total) {
              $factura['totalTVA']+=(float)$total->TaxAmount;
              $factura['totalFaraTVA']+=(float)$total->TaxableAmount;
              $factura['TVA'][(float)$total->TaxCategory->Percent]=array(
                'totalTVA'=>(float)$total->TaxAmount,
                'totalFaraTVA'=>(float)$total->TaxableAmount,
              );
            }
            $factura['total']=$factura['totalTVA']+$factura['totalFaraTVA'];

            $dateCompanie=xml2pdfParseID(array(
              count($xml->xpath('/Invoice/AccountingSupplierParty/Party/PartyIdentification/ID'))?(string)$xml->xpath('/Invoice/AccountingSupplierParty/Party/PartyIdentification/ID')[0][0]:'',
              count($xml->xpath('/Invoice/AccountingSupplierParty/Party/PartyLegalEntity/CompanyID'))?(string)$xml->xpath('/Invoice/AccountingSupplierParty/Party/PartyLegalEntity/CompanyID')[0][0]:'',
              count($xml->xpath('/Invoice/AccountingSupplierParty/Party/PartyLegalEntity/CompanyLegalForm'))?(string)$xml->xpath('/Invoice/AccountingSupplierParty/Party/PartyLegalEntity/CompanyLegalForm')[0][0]:'',
              count($xml->xpath('/Invoice/AccountingSupplierParty/Party/PartyTaxScheme/CompanyID'))?(string)$xml->xpath('/Invoice/AccountingSupplierParty/Party/PartyTaxScheme/CompanyID')[0][0]:'',
            ));

            $factura['firmaNume']=(string)$xml->xpath('/Invoice/AccountingSupplierParty/Party/PartyLegalEntity/RegistrationName')[0][0];
            $factura['firmaNrRegCom']=$dateCompanie['nrRegCom'];
            $factura['firmaCIF']=$dateCompanie['CIF'];
            $factura['firmaAdresa']=(string)$xml->xpath('/Invoice/AccountingSupplierParty/Party/PostalAddress/StreetName')[0][0];
            $factura['firmaLocalitate']=preg_replace('/(SECTOR)([0-9]{1})/','Sector \\2',(string)$xml->xpath('/Invoice/AccountingSupplierParty/Party/PostalAddress/CityName')[0][0]);
            $factura['firmaJudet']=$judete[preg_replace('/^(RO-)/','',(string)$xml->xpath('/Invoice/AccountingSupplierParty/Party/PostalAddress/CountrySubentity')[0][0])];
            $factura['firmaCont']=count($xml->xpath('/Invoice/PaymentMeans/PayeeFinancialAccount/ID'))?(string)$xml->xpath('/Invoice/PaymentMeans/PayeeFinancialAccount/ID')[0][0]:'';
            $factura['firmaContNume']=count($xml->xpath('/Invoice/PaymentMeans/PayeeFinancialAccount/Name'))?(string)$xml->xpath('/Invoice/PaymentMeans/PayeeFinancialAccount/Name')[0][0]:'';
            $factura['firmaBanca']=count($xml->xpath('/Invoice/PaymentMeans/PayeeFinancialAccount/FinancialInstitutionBranch/ID'))?(string)$xml->xpath('/Invoice/PaymentMeans/PayeeFinancialAccount/FinancialInstitutionBranch/ID')[0][0]:'';

            $factura['produse']=array();
            $lines=$xml->xpath('/Invoice/InvoiceLine');
            foreach($lines as $line) {
              $produs=array(
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
              if (isset($unitati[$produs['um']])) {
                $produs['umText']=$unitati[$produs['um']];
              }
              $factura['produse'][]=$produs;
            }

            if (count($xml->xpath('/Invoice/AllowanceCharge'))) { // AllowanceCharge la nivel de document (nu face la nivel de produs, todo)
              foreach($xml->xpath('/Invoice/AllowanceCharge') as $ac) {
                $produs=array(
                  'produs'=>$taxeBonus[(string)$ac->xpath('AllowanceChargeReasonCode')[0]]?$taxeBonus[(string)$ac->xpath('AllowanceChargeReasonCode')[0]]:'',
                  'descriere'=>count($ac->xpath('AllowanceChargeReason'))?trim((string)$ac->xpath('AllowanceChargeReason')[0]).' ('.trim((string)$ac->xpath('AllowanceChargeReasonCode')[0]).')':'Cod: '.(string)$ac->xpath('AllowanceChargeReasonCode')[0],
                  'nota'=>'',
                  'codVanzator'=>'',
                  'pretFaraTVA'=>(float)$ac->xpath('Amount')[0],
                  'moneda'=>(string)$ac->xpath('Amount')[0]->attributes()->currencyID,
                  'cantitate'=>1,
                  'um'=>'buc.',
                  'TVA'=>(float)$ac->xpath('TaxCategory/Percent')[0],
                  'totalFaraTVA'=>(float)$ac->xpath('Amount')[0],
                );
                if ((string)$ac->xpath('ChargeIndicator')[0]==='false') { // Este Allowance?
                  $produs['pretFaraTVA']=-$produs['pretFaraTVA'];
                  $produs['totalFaraTVA']=-$produs['totalFaraTVA'];
                }
                $factura['produse'][]=$produs;
              }
            }

            $factura['fisiere']=array();
            $files=$xml->xpath('/Invoice/AdditionalDocumentReference/Attachment/EmbeddedDocumentBinaryObject');
            if (count($files)) {
              foreach($files as $file) {
                if ((string)$file[0]['mimeCode']==='application/pdf') {
                  $factura['fisiere'][]=base64_decode($file[0]);
                }
              }
            }

            return $factura;
          } else {
            // Error: Invalid date
            return 4;
          }
        } else {
          // Error: Date not found
          return 3;
        }
      } else {
        // Error: Not invoice
        return 2;
      }
    } else {
      // Error: Invalid XML
      return 1;
    }
  }

?>
