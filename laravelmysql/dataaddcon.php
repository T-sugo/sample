<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Http\Requests;
use Response; //Response::makeのため
use Input; //Input::getのため
use DB; //DB操作のため
 
class dataaddcon extends Controller
{
    public function add()
    {
      $name=Input::get('name'); //名前
      $password=Input::get('password'); //パスワード
 
      $name=htmlspecialchars($name); //フォーム欄のコード埋め込みを防ぐ
      $password=htmlspecialchars($password);
 
      try //実行
      {
        DB::insert('insert into userTBL (NAME,PW) values (?,?)',[$name,md5($password)]); //データ登録
        return Response::make('0'); //データ登録成功
      }
      catch(Exception $e) //例外
      {
        return Response::make('1'); //データ登録失敗
      }
    }
}
