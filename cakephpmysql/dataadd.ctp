<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title></title>
    <?= $this->Html->script('jquery-2.2.3.min.js') ?> //jqueryを読み込む
    <?= $this->Html->script('add.js') ?> //ajaxのjsファイルを読み込む     
  </head>
  <body>
    <?= $this->fetch('content') ?> //bodyの中身
  </body>
</html>
