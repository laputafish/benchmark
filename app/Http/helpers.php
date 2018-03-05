<?php

function logx($str, $condition=true) {
    if($condition) {
      echo htmlentities($str); nl();
    }
}
function nl() {
  echo "<br/>\n";
}

function lowerTags($matches) {
  return strtolower($matches[1]);
}

function removeEnclosingTag( $str, $tag ) {
  $pattern = '/<'.$tag.'\s*[^>]*>(.*?)<\/'.$tag.'>/';
  return preg_replace( $pattern, '$1', $str );
}
?>
