<?php

  function xml2pdfDrawInit($pdf,&$vars) { // inițializează funcțiile de desenare
    if (isset($pdf) && gettype($pdf)==='object' && method_exists($pdf,'SetAutoPageBreak')) {
      $pdf->SetAutoPageBreak(false);
    } else {
      trigger_error("PDF not initialized",E_USER_ERROR);
    }

    $vars=array(
      '_page'=>'1',
    );
  }

  function xml2pdfDrawEval($expr,$vars) { // rezolvă expresii aritmetice (*, /, +, -) cu variabile și fără paranteze
    if (gettype($expr)=='string') {
      // verifică dacă variabilele folosite există și au valori numerice și le înlocuiește cu valorile lor

      preg_match_all('/(\{\{[a-zA-Z0-9_]+\}\})/',$expr,$matches,PREG_SET_ORDER);
      if (count($matches)) {
        for ($i=0; $i<count($matches); $i++) {
          $var=trim($matches[$i][0],'{}');
          if (!isset($vars[$var])) {
            trigger_error("Undefined variable \"{$var}\"",E_USER_ERROR);
          } elseif (!preg_match('/^[0-9\-\.]+$/',(string)$vars[$var])) {
            trigger_error("Non numeric value in variable \"{$vars[$var]}\"",E_USER_ERROR);
          } else {
            $expr=str_replace('{{'.$var.'}}',$vars[$var],$expr);
          }
        }
      }

      // înlocuiește "-" cu "+-"

      $expr=preg_replace('/([^+])(\-)/','\\1+-',trim(str_replace(' ','',$expr)));

      // sparge expresia în token-uri pe baza operatorilor *, /, +

      $exprTokenized=preg_split('/(?=[\+\*\/])|(?<=[\+\*\/])/',$expr,-1,PREG_SPLIT_NO_EMPTY);

      // verifică dacă expresia începe sau se termină cu operatori

      if (in_array($exprTokenized[0],array('+','*','/')) || in_array($exprTokenized[count($exprTokenized)-1],array('+','*','/'))) {
        trigger_error("Expression starts or ends with an operand",E_USER_ERROR);
      }

      // verifică dacă există operatori consecutivi sau token-urile au valori nenumerice

      for ($i=0; $i<count($exprTokenized); $i++) {
        if (in_array($exprTokenized[$i],array('+','*','/'))) {
          if (isset($exprTokenized[$i-1]) && in_array($exprTokenized[$i-1],array('+','*','/'))) {
            trigger_error("Consecutive operands in expression",E_USER_ERROR);
          }
        } elseif (!preg_match('/^-?[0-9\.]+$/',$exprTokenized[$i])) {
          trigger_error("Non numeric value in expression \"{$exprTokenized[$i]}\"",E_USER_ERROR);
        }
      }

      // efectuează înmulțirea și împărțirea

      $i=0;
      do {
        if ($exprTokenized[$i]==='*') {
          $exprTokenized[$i-1]=(float)$exprTokenized[$i-1]*(float)$exprTokenized[$i+1];
          array_splice($exprTokenized,$i,2);
          $i++;
        }
        if ($exprTokenized[$i]==='/') {
          $exprTokenized[$i-1]=(float)$exprTokenized[$i-1]/(float)$exprTokenized[$i+1];
          array_splice($exprTokenized,$i,2);
          $i++;
        }
        $i++;
      } while ($i<count($exprTokenized));

      // efectuează adunările

      $exprResult=(float)$exprTokenized[0];
      if (count($exprTokenized)>1) {
        for ($i=1; $i<count($exprTokenized); $i++) {
          if ($exprTokenized[$i-1]==='+') {
            $exprResult+=(float)$exprTokenized[$i];
          }
        }
      }

      return $exprResult;
    } else {
      return $expr;
    }
  }

  function xml2pdfDraw($t,$pdf,&$vars) { // procesează o instrucțiune de desenare
    switch($t[0]) {
      // '_newPageBefore', array(...) (definește ce se întâmplă înainte să se adauge o pagină nouă)

      case '_newPageBefore':
        $vars['_newPageBefore']=$t[1];
        break;

       // '_newPageAfter', array(...) (definește ce se întâmplă după ce se adaugă o pagină nouă)

      case '_newPageAfter':
        $vars['_newPageAfter']=$t[1];
        break;

      // 'newPageIf', y (poziția curentă > y) (dacă poziția curentă e mai mare de "y" se trece la o pagină nouă)
      // 'newPageIf', y (poziția curentă > y), array(text, width, size, bold)[, array ...] (dacă poziția curentă plus înălțimea textelor depășesc "y" se trece la o pagină nouă)

      case 'newPageIf':
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

      // 'setY', value (mută poziția y curentă)

      case 'setY':
        $y=xml2pdfDrawEval($t[1],$vars);
        $pdf->setXY($pdf->getX(),$y);
        break;

      // 'getY', var (salvează pozița y curentă)

      case 'getY':
        $vars[$t[1]]=$pdf->getY();
        break;

       // 'moveX', value (mută poziția x curentă)

      case 'moveX':
        $pdf->setXY($pdf->getX()+$t[1],$pdf->getY());
        break;

       // 'moveY', value (mută poziția y curentă)

      case 'moveY':
        $pdf->setXY($pdf->getX(),$pdf->getY()+$t[1]);
        break;

       // 'setVar', var, value (definește o variabilă)

      case 'setVar':
        $vars[$t[1]]=$t[2];
        break;

       // 'line', x1, y2, x2, y2 (desenează o linie)

      case 'line':
        $pdf->line($t[1],xml2pdfDrawEval($t[2],$vars),$t[3],xml2pdfDrawEval($t[4],$vars),array('width'=>0.2,'color'=>array(0,0,0,100)));
        break;

      // 'rect', x1, y1, x2, y2 (desenează un dreptunghi)

      case 'rect':
        $y1=xml2pdfDrawEval($t[2],$vars);
        $y2=xml2pdfDrawEval($t[4],$vars);
        $pdf->rect($t[1],$y1,$t[3]-$t[1],$y2-$y1,isset($t[5])?'DF':'',isset($t[5])?array('0000'=>array()):array('LRTB'=>array('width'=>0.2,'color'=>array(0,0,0,100))),array());
        break;

      /*
        'text', text, x, y[, size][, bold][, array(x2, alignH, alignV='B/M'[, y2])][, isNextLine]

        Desenează un text:
          - text poate conține și {{variabile}}
          - dacă x sau y sunt false se folosește poziția curentă
          - dacă x2 este definit se folosește pentru alinierea textului
          - alignH poate fi 'L','C','R','J' (implicit este 'L')
          - dacă alignV = 'B' atunci textul se desenează de la poziția curentă în sus, dacă alignV = 'M' atunci se centrează vertical între "y" și "y2"
          - dacă isNextLine este true atunci poziția curentă pe y se mută după text, altfel se mută orizontal după text
      */

      case 'text':
        list(,$text,$x,$y,$size,$isBold,$align,$isNextLine)=array_pad($t,8,null);

        foreach($vars as $p=>$val) {
          if (gettype($val)!=='array') {
            $text=str_replace('{{'.$p.'}}',(string)$val,$text);
          }
        }

        $pdf->SetFont('freesans',(!is_null($isBold) && $isBold)?'B':'',!is_null($size)?$size:9);
        if ($y!==false) {
          $pdf->SetY(xml2pdfDrawEval($y,$vars));
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

       // 'image', file, x, y, h (importă o imagine în format PNG)

      case 'image':
        $pdf->image($t[1],$t[2],xml2pdfDrawEval($t[3],$vars),0,$t[4],'PNG','','T');
        break;
    }
  }

  function xml2pdfDrawAll($draw,$pdf,&$vars) { // procesează toate instrucțiunile de desenare
    foreach($draw as $t) {
      xml2pdfDraw($t,$pdf,$vars);
    }
  }

  // xml2pdfDrawInit($pdf,$vars);
  //
  // echo xml2pdfDrawEval('-{{_page}}*5+6/2-5*4.3-1',$vars).'<br>';
  // echo -2*5+6/2-5*4.3-1;

?>
