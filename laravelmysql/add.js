$(function()
{
  $('#submit').click(function() //送信ボタンをクリック
  {
    if($('#name').val()==''||$('#password').val()=='') //名前欄かパスワード欄どちらかが空欄だったら
    {
      alert('名前かパスワードが入力されていません！'); //アラート
    }
    else //空欄がない
    {
      $.ajax(
        {
          type: "POST", //POSTで渡す
          url: "add", //POST先
          data:
          {
            "name":$('#name').val(), //名前
            "password":$('#password').val() //パスワード
          },
          success: function(hoge) //通信成功、dataaddcon.phpからの返り値を受け取る
          {
            if(hoge==0) //返り値が0→成功
            {
              alert('正常終了しました');
            }
            else if(hoge==1) //返り値が1→失敗
　　　　　　　 {
              alert('失敗しました');
            }
          },
          error: function(XMLHttpRequest,textStatus,errorThrown) //通信失敗
          {           
            alert('処理できませんでした');
          }
        });
        return false; //ページが更新されるのを防ぐ
      }
    });
});
