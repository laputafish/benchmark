<?php namespace App\Http\Controllers;

use  \DOMDocument;
use App\Models\Cpu;
use App\Models\CpuType;

class CpuController extends Controller {
  public function get() {
    $cpuBenchmarkUrl = 'https://www.cpubenchmark.net/cpu_list.php';
    $html = file_get_contents( $cpuBenchmarkUrl );
//echo '1111111111';
    $html = $this->removeBefore($html, '<div class="content"');
  //  echo '22222222222';
    $html = $this->removeAfter($html, '<div class="contentborder"');
    //echo '33333333333';
    $html = $this->removeTag($html, 'table');
    //echo '444444444444';
    // $html = $this->removeTag($html, 'table');
    // $html = $this->removeTag($html, 'table');
//dd($html);
//echo 'yyy';
    $html = $this->removeTag($html, 'p');
    $html = $this->removeTag($html, 'script');
    $html = $this->removeTag($html, 'script');
    $html = $this->removeTag($html, 'script');
    //$html = $this->removeTag($html, 'style');
    //$html = $this->removeTag($html, 'p');

    //$html = $this->removeAttributes( $html );
    $html = preg_replace_callback('/(<\/?\w+.*?>)/', function($m) {
      return strtolower($m[1]);}, $html);
    $html = preg_replace( '/>[\t\r\n\s]+</','><',$html);
    //
    // $html = str_replace( 'CENTER>', 'center>', $html);
    // $html = str_replace( '<TABLE', '<table', $html );
    // $html = str_replace( 'TABLE>', 'table>', $html );

    $cpuCollections = [];
    $this->getCpuCollections( $html, $cpuCollections );
    //dd( $cpuCollections );
// $doc = new DOMDocument('1.0');
// $doc->formatOutput = true;
// $doc->loadXml( $html );
// return $doc->saveXML( $dom );
    // while(strpos($html,'script') !== false) {
    //   $html = $this->removeTag($html, 'script');
    // }
    return 'collection count = '. count($cpuCollections);
  }

  private function removeAttributes( $html ) {
      $pattern = '/<([a-zA-Z]+)\s+[^>]+>/';
      return preg_replace( $pattern, '<$1>', $html );
  }

  private function getContainers($xml, $tag) {
    $result = [];
    $startTag = $tag;
    $endTag = substr_replace( $startTag, '/', 1, 0 );
    //dd('endTag = '.$endTag);
    // print_r( $xml );
    // echo '****************';
    // echo 'startTag = '.$startTag; nl();
    // echo 'endTag = '.$endTag; nl();
    $startPos = strpos( $xml, $startTag );
    $endPos = strpos($xml, $endTag );
// echo 'startPos = '.$startPos; nl();
// echo 'endPos = '.$endPos; nl();
    $html = $xml;
    while(($startPos !== false)&&($endPos !== false)) {
      $contentLen = $endPos + strlen($endTag) - $startPos;
      $containerContent = substr( $html, $startPos, $contentLen );
      $result[] = $containerContent;
      $html = substr_replace( $html, '', $startPos, $contentLen );

      $startPos = strpos($html, $startTag );
      $endPos = strpos($html, $endTag);
    }
    return $result;
  }

  private function getHeader( $html ) {
    $pattern = '/<thead><tr>(.*?)<\/tr><\/thead>/';
    $matches = null;
    $result = preg_match_all( $pattern, $html, $matches );
    $headerStr = $matches[1][0];

    $pattern = '/<th>(.*?)<\/th>/';
    $matches = null;
    $result = preg_match_all($pattern, $headerStr, $matches );

    $headers = $matches[1];
    $result = [];
    dd($headers);
    foreach( $headers as $header ) {
      $result[] = [
        'cpu_name'=>$header[0],
        'passmark'=>$header[1],
        'rank'=>$header[2]
      ];
    }
    return $result;
  }

  private function getCpuCollections( $xml, $collections ) {
    // echo 'xxxxx getCpuCollections';
    $cpuTypes = CpuType::all()->toArray();
    Cpu::truncate();
    $htmlCollections = $this->getContainers($xml, '<center>');
    $c = 0;
    foreach( $htmlCollections as $i=>$htmlCollection ) {
      // if($i==1) {
      //   dd($htmlCollection);
      // }
      $cpuTypeId = $cpuTypes[$i]['id'];
      $containers = $this->getContainers($htmlCollection, '<tbody>');
      // echo '#'.$i.': count = '.count($containers); nl();
      if(count($containers)>0) {
        $c++;
        $cpuArray = $this->parseTBody( $containers[0] );
        foreach( $cpuArray as $j=>$cpuInfo ) {
          $cpuInfo['cpu_type_id'] = $cpuTypeId;
          CPU::create($cpuInfo);
        }
        // echo 'cpuArray count = '.count($cpuArray); nl();
        // CPU::insert( $cpuArray );
      }
    }
    // dd('ok collection count = '.$c );
    return $htmlCollections;
    // $result = 0;
    // $matches = null;
    // $pattern = '/<center>/i';
    // $result = preg_match_all( $pattern, $xml, $matches );
    // if(count($matches)>0) {
    //   $collections = $matches[0];
    // }
    // return $matches;
  }

  private function parseTBody( $body ) {
      $pattern = '/<tr\sid="cpu([0-9]+)">(.*?)<\/tr>/';
      $matches = null;
      $result = [];
      if(preg_match_all( $pattern, $body, $matches )>0) {
        foreach( $matches[0] as $i=>$matchRow ) {
          $result[] =
            array_merge(
              ['cpu_id'=>(int) $matches[1][$i]],
              $this->parseCpuRow( $matchRow )
            );
        }
      }
      return $result;
  }

  private function parseCpuRow( $row ) {
      $pattern = '/<td>(.*?)<\/td>/';
      $matches = null;
      $result = null;
      if(preg_match_all( $pattern, $row, $matches )>0) {
        $result = [
          'name' => removeEnclosingTag($matches[1][0], 'a'),
          'passmark' => (int) $matches[1][1],
          'rank' => (int) $matches[1][2]
        ];
      }
      return $result;
  }


  private function removeBefore( $html, $needle, $keep=true ) {
    $pos = strpos( $html, $needle );
    if($pos === false) {
      return $html;
    }
    else {
      if($keep) {
        return substr($html, $pos);
      } else {
        $len = strlen($needle);
        return substr($html, $pos + $len );
      }
    }
  }

  private function removeAfter( $html, $needle) {
    $pos = strpos($html, $needle);
    if($pos === false) {
      return $html;
    } else {
      return substr($html, 0, $pos );
    }
  }

  private function removeTag( $html, $tag ) {
    $chk = false; //$tag=='table';     // $chk
    logx('removeTag :: tag= '.$tag, $chk);
    logx('tag = '.$tag, $chk);
    $startTag = '<'.$tag;
    logx('startTag = '.$startTag, $chk);
    $endTag = '</'.$tag.'>';

    $posStart = strpos($html, $startTag );
    if($tag == 'script') {
      logx('posStart = '.$posStart, $chk  );
      logx('removeTag :: startTag = '.$startTag, $chk);
      logx( 'removeTag :: endTag = '.$endTag, $chk);
    }
    if($posStart === false) {
      return $html;
    }
    else {
      logx('removeTag :: startTag = '.$startTag, $chk);
      logx('removeTag :: endTag = '.$endTag, $chk);
      //logx($html, $chk);
      //logx('xxxxxxxxxxxx',$chk);
      $startContent = $this->removeAfter( $html, $startTag );
      //logx( $startContent, $chk);
      //logx('**************', $chk);

      $endContent = $this->removeBefore( $html, $endTag, $keep=false );
      //logx( $endContent, $chk );
      //logx( '88888888888888', $chk);
      if($chk) {
//        dd('ok');
      }
      return $startContent.$endContent;
    }
  }

}
