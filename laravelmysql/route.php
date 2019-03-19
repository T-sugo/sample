Route::get('dataadd', function()
{
  return view('dataadd'); //登録画面表示
});

Route::any('add','dataaddcon@add'); //データ登録
