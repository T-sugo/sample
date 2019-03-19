<?php
 
namespace App\Controller;
 
use Cake\Core\Configure;
use Cake\Network\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;
 
class DataaddController extends AppController
{
  public function initialize()
  {
    $this->viewBuilder()->layout('dataadd'); //レイアウト読み込み
  }
  public function dataadd()
  {
 
  }
}
