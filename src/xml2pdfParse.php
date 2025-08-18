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
      } elseif (preg_match_all('/([JFC]{1}[0-9]{2}\/[0-9]+\/[0-9\.]+)/',$v,$matches)) { // J vechi
        $nrRegCom=$matches[0][0];
      } elseif (preg_match_all('/([JFC]{1}[0-9]{13})/',$v,$matches)) { // J nou
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
    $acTipuri=array(
      // Allowance
      '95'=>'Discount',
      // Charge
      'TV'=>'Transport',
    );

    $xmlString=preg_replace('/([ \n]{1}(xmlns|xsi)(:[a-zA-Z0-9]+){0,1}="[^"]+")/','',$xmlString);
    $xmlString=str_replace(array('<cbc:','</cbc:','<cac:','</cac:','<ubl:','</ubl:'),array('<','</','<','</','<','</'),$xmlString);
    $xml=simplexml_load_string($xmlString);

    if ($xml!==false) {
      $tipDocument=false;
      if (count($xml->xpath('/Invoice'))) {
        $tipDocument='Invoice';
      } elseif (count($xml->xpath('/CreditNote'))) {
        $tipDocument='CreditNote';
      }

      if ($tipDocument!==false) {
        $dataFactura=$xml->xpath('/'.$tipDocument.'/IssueDate');
        if (count($dataFactura)) {
          $dataFactura=(string)$dataFactura[0][0];
          if (preg_match('/^[0-9]{4}\-[0-9]{2}-[0-9]{2}$/',$dataFactura)) {
            list($y,$m,$d)=explode('-',$dataFactura);
            $numar=(string)$xml->xpath('/'.$tipDocument.'/ID')[0][0];

            $dateCompanie=xml2pdfParseID(array(
              count($xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PartyIdentification/ID'))?(string)$xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PartyIdentification/ID')[0][0]:'',
              count($xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PartyLegalEntity/CompanyID'))?(string)$xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PartyLegalEntity/CompanyID')[0][0]:'',
              count($xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PartyTaxScheme/CompanyID'))?(string)$xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PartyTaxScheme/CompanyID')[0][0]:'',
            ));

            $factura=array(
              'tipDocument'=>$tipDocument==='Invoice'?'Factura':'Nota de creditare',
              'nume'=>trim((string)$xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PartyLegalEntity/RegistrationName')[0][0]),
              'nrRegCom'=>trim($dateCompanie['nrRegCom']),
              'CIF'=>trim($dateCompanie['CIF']),
              'adresa'=>trim((string)$xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PostalAddress/StreetName')[0][0]),
              'localitate'=>trim(preg_replace('/(SECTOR)([0-9]{1})/','Sector \\2',(string)$xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PostalAddress/CityName')[0][0])),
              'judet'=>trim($judete[preg_replace('/^(RO-)/','',(string)$xml->xpath('/'.$tipDocument.'/AccountingCustomerParty/Party/PostalAddress/CountrySubentity')[0][0])]),
            );

            if (preg_match('/^[A-Z]{0,2}[0-9]+$/',$factura['nrRegCom'])) {
              list($factura['nrRegCom'],$factura['CIF'])=array($factura['CIF'],$factura['nrRegCom']);
            }

            $factura['dataFactura']=$dataFactura;
            $factura['numar']=$numar;
            $factura['tip']=(string)$xml->xpath('/'.$tipDocument.'/'.$tipDocument.'TypeCode')[0][0];
            if (isset($tipuriDocument[$factura['tip']])) {
              $factura['tipText']=$tipuriDocument[$factura['tip']];
            }
            $factura['instrumentPlata']=count($xml->xpath('/'.$tipDocument.'/PaymentMeans/PaymentMeansCode'))?(string)$xml->xpath('/'.$tipDocument.'/PaymentMeans/PaymentMeansCode')[0][0]:'-';
            if (isset($instrumentePlata[$factura['instrumentPlata']])) {
              $factura['instrumentPlataText']=$instrumentePlata[$factura['instrumentPlata']];
            }
            if (count($xml->xpath('/'.$tipDocument.'/Note'))) {
              $factura['nota']='';
              for ($i=0; $i<count($xml->xpath('/'.$tipDocument.'/Note')); $i++) {
                $factura['nota'].=($factura['nota']?"\n":'').trim((string)$xml->xpath('/'.$tipDocument.'/Note')[$i][0]);
              }
            }

            $factura['TVA']=array();
            $factura['totalTVA']=0;
            $factura['totalFaraTVA']=0;
            $totaluri=$xml->xpath('/'.$tipDocument.'/TaxTotal/TaxSubtotal');
            foreach($totaluri as $total) {
              $factura['totalTVA']+=(float)$total->TaxAmount;
              $factura['totalFaraTVA']+=(float)$total->TaxableAmount;
              $factura['TVA'][(float)$total->TaxCategory->Percent]=array(
                'totalTVA'=>(float)$total->TaxAmount,
                'totalFaraTVA'=>(float)$total->TaxableAmount,
              );
            }
            $factura['total']=$factura['totalTVA']+$factura['totalFaraTVA'];

            if ($tipDocument==='CreditNote') { // face totalurile negative la nota de creditare (todo: verificat dacă este corect)
              $factura['totalTVA']=-$factura['totalTVA'];
              $factura['totalFaraTVA']=-$factura['totalFaraTVA'];
              $factura['total']=-$factura['total'];
              array_walk_recursive($factura['TVA'],function(&$v,$k) {
                $v=-$v;
              });
            }

            $dateCompanie=xml2pdfParseID(array(
              count($xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PartyIdentification/ID'))?(string)$xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PartyIdentification/ID')[0][0]:'',
              count($xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PartyLegalEntity/CompanyID'))?(string)$xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PartyLegalEntity/CompanyID')[0][0]:'',
              count($xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PartyLegalEntity/CompanyLegalForm'))?(string)$xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PartyLegalEntity/CompanyLegalForm')[0][0]:'',
              count($xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PartyTaxScheme/CompanyID'))?(string)$xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PartyTaxScheme/CompanyID')[0][0]:'',
            ));

            $factura['firmaNume']=(string)$xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PartyLegalEntity/RegistrationName')[0][0];
            $factura['firmaNrRegCom']=$dateCompanie['nrRegCom'];
            $factura['firmaCIF']=$dateCompanie['CIF'];
            $factura['firmaAdresa']=(string)$xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PostalAddress/StreetName')[0][0];
            $factura['firmaLocalitate']=preg_replace('/(SECTOR)([0-9]{1})/','Sector \\2',(string)$xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PostalAddress/CityName')[0][0]);
            $factura['firmaJudet']=$judete[preg_replace('/^(RO-)/','',(string)$xml->xpath('/'.$tipDocument.'/AccountingSupplierParty/Party/PostalAddress/CountrySubentity')[0][0])];
            $factura['firmaCont']=count($xml->xpath('/'.$tipDocument.'/PaymentMeans/PayeeFinancialAccount/ID'))?(string)$xml->xpath('/'.$tipDocument.'/PaymentMeans/PayeeFinancialAccount/ID')[0][0]:'';
            $factura['firmaContNume']=count($xml->xpath('/'.$tipDocument.'/PaymentMeans/PayeeFinancialAccount/Name'))?(string)$xml->xpath('/'.$tipDocument.'/PaymentMeans/PayeeFinancialAccount/Name')[0][0]:'';
            $factura['firmaBanca']=count($xml->xpath('/'.$tipDocument.'/PaymentMeans/PayeeFinancialAccount/FinancialInstitutionBranch/ID'))?(string)$xml->xpath('/'.$tipDocument.'/PaymentMeans/PayeeFinancialAccount/FinancialInstitutionBranch/ID')[0][0]:'';

            $factura['produse']=array();
            $lines=$xml->xpath('/'.$tipDocument.'/'.$tipDocument.'Line');
            foreach($lines as $line) {
              $produs=array(
                'produs'=>trim((string)$line->Item->Name),
                'descriere'=>trim((string)$line->Item->Description),
                'nota'=>trim((string)$line->Note),
                'codVanzator'=>trim((string)$line->Item->SellersItemIdentification->ID),
                'pretFaraTVA'=>(float)$line->Price->PriceAmount,
                'moneda'=>(string)$line->Price->PriceAmount->attributes()->currencyID,
                'cantitate'=>$tipDocument==='Invoice'?(int)$line->InvoicedQuantity:-(int)$line->CreditedQuantity, // face cantitățile negative la nota de creditare (todo: verificat dacă este corect)
                'um'=>$tipDocument==='Invoice'?(string)$line->InvoicedQuantity->attributes()->unitCode:(string)$line->CreditedQuantity->attributes()->unitCode,
                'TVA'=>(float)$line->Item->ClassifiedTaxCategory->Percent,
                'totalFaraTVA'=>(float)$line->LineExtensionAmount,
              );
              if (isset($unitati[$produs['um']])) {
                $produs['umText']=$unitati[$produs['um']];
              }
              $factura['produse'][]=$produs;
            }

            if ($tipDocument==='Invoice') { // ignoră AllowanceCharge la nota de creditare (todo: verificat dacă este corect)
              if (count($xml->xpath('/'.$tipDocument.'/AllowanceCharge'))) { // AllowanceCharge la nivel de document (nu face la nivel de produs, todo?)
                foreach($xml->xpath('/'.$tipDocument.'/AllowanceCharge') as $ac) {
                  $acProdus='';
                  if (count($ac->xpath('AllowanceChargeReasonCode')) && $acTipuri[(string)$ac->xpath('AllowanceChargeReasonCode')[0]]) {
                    $acProdus=$acTipuri[(string)$ac->xpath('AllowanceChargeReasonCode')[0]];
                  }

                  $acDescriere='';
                  if (count($ac->xpath('AllowanceChargeReason'))) {
                    $acDescriere=trim((string)$ac->xpath('AllowanceChargeReason')[0]);
                    if (count($ac->xpath('AllowanceChargeReasonCode')) && trim((string)$ac->xpath('AllowanceChargeReasonCode')[0])) {
                      $acDescriere.=' (Cod: '.trim((string)$ac->xpath('AllowanceChargeReasonCode')[0]).')';
                    }
                  }

                  $produs=array(
                    'produs'=>$acProdus,
                    'descriere'=>$acDescriere,
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
            }

            $factura['fisiere']=array();
            $files=$xml->xpath('/'.$tipDocument.'/AdditionalDocumentReference/Attachment/EmbeddedDocumentBinaryObject');
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
        // Error: Unsupported document
        return 2;
      }
    } else {
      // Error: Invalid XML
      return 1;
    }
  }

?>
