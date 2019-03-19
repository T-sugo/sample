<?php
 
namespace App\Controller;
 
use Cake\Core\Configure;
use Cake\Network\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;
use Cake\ORM\TableRegistry;  //テーブル操作のため追加
 
class AddController extends AppController
{
  public function add()
  {
    $this->autoRender=FALSE; //ページの自動レンダリング機能をオフにする
    $name=$_POST['name']; //POSTで受け取った名前
    $password=$_POST['password']; //POSTで受け取ったパスワード
 
    $name=htmlspecialchars($name); //フォーム欄のコード埋め込みを防ぐ
    $password=htmlspecialchars($password);
 
    try //実行
    {
      $usertbls=TableRegistry::get('Usertbls'); //テーブルを取得
      $query=$usertbls->query(); //テーブルでクエリ文を使用することを宣言
      $query->insert(['NAME','PW']) //NAMEとPWの二つのカラムにデータを挿入する文
      ->values(['NAME'=>$name,'PW'=>md5($password)]) //名前をNAMEにパスワードを暗号（md5）化してPWに挿入
      ->execute(); //実行
 
      echo('0'); //データ登録成功
    }
    catch (Exception $e) //例外
    {
      echo('1'); //データ登録失敗（0とか1に特に意味はない）
    }
  }
}
