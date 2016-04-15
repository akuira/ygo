<?php
$str=str_replace("@card ","",$_POST['text']);

if(empty($str)) {
  $message = '何か入力して';
  $payload = array('text' => $message); 
  echo json_encode($payload);
  exit;
}

$key_word = preg_replace("/\s/", "・" , $str);
$key_word_hira = mb_convert_kana($key_word,"cRV",'UTF-8');
$key_word_nospace = preg_replace("/・/", "" , $str);
$key_word_nospace_hira = mb_convert_kana($key_word_nospace,"cRV",'UTF-8');
//たまたま見つけたリストを参照 
//http://www.metatubo.com/blog/category/%E3%82%AB%E3%83%BC%E3%83%89%E5%90%8D%E5%A4%89%E6%8F%9B%E8%BE%9E%E6%9B%B8/
$path = 'list.txt';
$temp_list = array();
foreach ( file($path) as $line) {
  if ( strpos($line, $key_word) !== false || strpos($line, $key_word_hira) !== false || strpos($line, $key_word_nospace) !== false || strpos($line, $key_word_nospace_hira) !== false) {
    //カード名と完全一致するものがあればそこで終了
    if (strpos($line, "《".$key_word ."》") !== false) {
      $name_list = array("《".$key_word ."》");
      break;
    } else {
      preg_match('/《.*》/s',$line,$card_name);
      $temp_list[] = $card_name[0];
    }
  }
}

if (!isset($name_list) && !empty($temp_list)) {
  $name_list = array_unique($temp_list,SORT_STRING);
  $count = count($name_list);
  if ($count > 5) {
    $message = $count. '件' . PHP_EOL;
    $message .='候補が6種類以上見つかったのでカード名を詳細に書いてください';
    $payload = array('text' => $message); 
  echo json_encode($payload);
    exit;
  }
} else if (!isset($name_list) && empty($temp_list)) {
  $message = 'カードが見つかりませんでした';
  $payload = array('text' => $message); 
  echo json_encode($payload);
  exit;
}

//遊戯王wiki(http://yugioh-wiki.net/)から引っ張ってくる
$result = array();
foreach($name_list as $card_name) {
  $encodename = rawurlencode(mb_convert_encoding($card_name,'euc-jp','utf-8'));
  $url = 'http://yugioh-wiki.net/index.php?'.$encodename;
  $html = file_get_contents($url);
  $html = mb_convert_encoding($html,'utf-8','euc-jp');
  //最初のpreタグがカードの説明らしいので持ってくる
  preg_match('/<pre>.*?<\/pre>/s',$html,$text);
  $res = preg_replace(array('/<pre>/','/<\/pre>/'),'```',$text[0]);
  $result[] = array($card_name,$url,$res);
}
$message = '';
foreach($result as $card){
  $message .= $card[0] . PHP_EOL;
  $message .= $card[1] . PHP_EOL;
  $message .= $card[2] . PHP_EOL;
}

$payload = array('text' => $message); 
echo json_encode($payload);
