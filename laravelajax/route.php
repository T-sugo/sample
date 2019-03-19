// ページの表示
Route::get('heisei', function()
{
  return view('heisei');
});

// 年数判定
Route::any('heiseisend','heiseicon@exchange');
