$(function()
{
  $('#send').click(function()
  {
    $.ajax(
      {
        type:"POST",
        url: "heiseisend", // /heiseisendにアクセスしてheiseicon.phpが発動
        data: {"seireki":$('#seireki').val()},
        success: function(hoge) // 接続が成功、heiseicon.phpから値を受け取る
        {
          alert(hoge); // 受け取った値をアラート表示
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) // 接続が失敗
        {          
          alert('エラーです！'); // エラーメッセージ
        }
      });
    return false;
  });
});
