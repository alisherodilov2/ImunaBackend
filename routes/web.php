<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::get('/', function () {

//     $url = 'https://api.telegram.org/bot7351622153:AAHHsdqMIyQ12BUcz8Oj9Q9HVL_H89mTLwY/sendMessage';

//   $res =   Http::post($url, [
//         'chat_id' => '-4096756392',
//         'text' => " Buyurtma   tekshirildi. pulni olishingiz mumkin!", // Message with emoji and markdown
//     ]);

    // return $res->json()['result']['message_id'];
    // soketSend('order',1111);
    // createDatabaseBackupAndSendToTelegram();

});
