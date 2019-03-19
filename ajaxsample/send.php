<?php
 
$seireki=$_POST['seireki']; //index.htmlで受け取った値を代入
$heisei=$seireki-1988; //平成○○年は西暦から1988引いた値
 
//以下条件によって返り値が変化
if($heisei==1){ //平成1年なら
     echo '平成元年です！';
}
 
else if($heisei<=0){
    echo 'その年は平成じゃないですよ！';
}
else if($heisei>=29){
     echo '未来のことはわからないです……。';
}
else{
     echo '平成'.$heisei.'年です！';
}
