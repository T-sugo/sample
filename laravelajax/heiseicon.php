<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Response; // return Response::make()を使用するために追加
use Input; // Input::get()を使用するために追加

class heiseicon extends Controller
{
    public function exchange()
    {
      $seireki=Input::get('seireki');　// heisei.jsから値を受け取る
      $heisei=$seireki-1988;

      if($heisei==1){
        return Response::make('平成元年です！'); // heisei.jsに値を返す
      }
      else if($heisei<=0){
        return Response::make('その年は平成じゃないですよ！'); // heisei.jsに値を返す
      }
      else if($heisei>=29){
        return Response::make('未来のことはわからないです……。'); //heisei.jeに値を返す
      }
      else{
        return Response::make('平成'.$heisei.'年です！'); //heisei.jsに値を返す
      }
    }
}
