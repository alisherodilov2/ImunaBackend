<?php

use App\Http\Controllers\Api\TelegramController;
use App\Http\Controllers\Api\V3\TelegramBotController;
use App\Models\Client;
use App\Models\ClinetPaymet;
use App\Models\DailyRepotClient;
use App\Models\DoctorBalance;
use App\Models\GraphArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/sms-send', function () {
  $response = Http::post('https://api.infobip.com/sms/1/text/single', [
    'from' => 'MyApp',
    'to' => '+998995192378',
    'text' => 'Salom, bu Laravel Infobip testi!'
]);

return $response->json();
  // return  sendSMS('995192378', 'Alisher', 'Alisher klinkasi', '2', 'https://smsapp.uz/new/devices.php', 'c80c0a631e5ed59b5fb0eed6d455357899de4e91', '1055');
});
Route::get('/audio', function () {
  $apiUrl = "https://www.speakatoo.com/api/v1/voiceapi";
  $apiKey = "TcatVfWe1269b1d31eb52192dee2b8cc82d30aa331Om7T2Gkl"; // API kalitingizni shu yerga joylashtiring
$text = "Assalom, bu Laravel Infobip testi!";
  $response = Http::withHeaders([
      'X-API-KEY' => $apiKey,
      'Content-Type' => 'application/json',
  ])->post($apiUrl, [
      "username" => "rasuljon",
      "password" => ";r&ZxZ!p&E.Rc6E",
      // rasuljon2000$$
      "tts_title" => "audio_output",
      "ssml_mode" => "0",
      "tts_engine" => "neural",
      "tts_format" => "mp3",
      "tts_text" => $text,  // Ovozga o‘tkaziladigan matn
      "tts_resource_ids" => "z9IXZivMt89b0c29fbff4cf7c8c97d2d8bd0818afSDZexwuIr", // O‘zbek ovoz ID-sini kiriting
      "synthesize_type" => "save"
  ]);

  if ($response->successful()) {
      $data = $response->json();
      return $data; // Foydalanuvchiga audio fayl URL qaytariladi
  }
  return 11;


// $response = Http::withHeaders([
//   'Content-Type' => 'application/json',
//   'x-rapidapi-host' => 'ai-voice-text-to-speach.p.rapidapi.com',
//   'x-rapidapi-key' => '024d9f64famshd8dcc4acb20f7d0p186ab1jsnf011cb6fc7d4',
// ])
// ->post('https://ai-voice-text-to-speach.p.rapidapi.com/makevoice?text=Hello!%20How%20are%20you%20doing%3F.%20Check%20%22About%22%20tab%20to%20see%20how%20to%20change%20the%20voice.&voice=m2', [
//   'key1' => 'value',
//   'key2' => 'value'
// ]);

// if ($response->successful()) {
//   return $response->body(); // Yoki kerakli javobni ishlatish
// } else {
//   return$response->body(); // Xato bo‘lsa statusni ko‘rsatish
// }


// $apiUrl = "https://api.ttsmaker.com/v1/tts";
//     $apiKey = "YOUR-API-KEY"; // API kalitingizni shu yerga joylashtiring

//     $response = Http::withHeaders([
//         'Content-Type' => 'application/json',
//     ])->post($apiUrl, [
//         "text" => 'Assalom, bu Laravel Infobip testi!', // Ovozga o‘tkaziladigan matn
//         "language" => "uz-UZ", // O‘zbek tili
//         "voice" => "YOUR_VOICE_ID", // TTSMaker-da mos ovozni tanlang
//         "api_key" => $apiKey,
//         "format" => "mp3" // Yoki "wav"
//     ]);

//     if ($response->successful()) {
//         $data = $response->json();
//         return $data['audio_url'] ?? null; // Audio fayl URL qaytariladi
//     }

//     return null; // Xatolik yuz bersa
//   // return  sendSMS('995192378', 'Alisher', 'Alisher klinkasi', '2', 'https://smsapp.uz/new/devices.php', 'c80c0a631e5ed59b5fb0eed6d455357899de4e91', '1055');
});



// !hushyor bolllll
// Route::get('/test-ewuygdsahdsa', function () {
//    Client::query()->delete();
//     GraphArchive::query()->delete();
// });

// Route::get('/test-1', function () {
//     $data =  DoctorBalance::whereNull('daily_repot_id')->get(['id', 'client_id']);
//     foreach ($data as $item) {
//         $find = DailyRepotClient::where('client_id', $item->client_id)->first();
//         if ($find) {
//             DoctorBalance::where('id', $item->id)->update(['daily_repot_id' => $find->daily_repot_id]);
//         }
//     }
//     $daily = DailyRepotClient::whereIn('client_id', $data->pluck('client_id'))->get();
//     return $data;
// });

Route::get('/test-1', function () {
    $client = Client::whereNotNull('parent_id')
        ->whereDate('created_at', '2024-12-20')
        ->where('user_id', 13)

        ->get();
        // $DailyRepotClientData = DailyRepotClient::where('user_id', 13)
        // ->whereDate('created_at', '2024-12-20')
        // ->get();
        // return $DailyRepotClientData;
    $ClinetPaymet = ClinetPaymet::whereDate('created_at', '2024-12-20')
        // ->get()
        ->where('user_id', 13);
    // ->pluck('client_id')->unique()->count();
    $DailyRepotClientData =     $client->map(function ($item) {
        return [
            'client_id' => $item->id,
            'daily_repot_id' => 12,
            'created_at' => Carbon::now()->subDay(), // Bir kun oldingi vaqt
            'updated_at' => Carbon::now()->subDay(), // Bir kun oldingi vaqt
        ];
    });
    // DailyRepotClient::insert($DailyRepotClientData->toArray());
    return [
        'client_count' => $ClinetPaymet->pluck('client_id')->unique()->count(),
        'total_price' => $client->sum('pay_total_price'),
        'balance' => $client->sum('pay_total_price'),
        'pay_total_price' => $ClinetPaymet->sum('pay_total_price'),
        'discount' => $ClinetPaymet->sum('discount'),
        'cash_price' => $ClinetPaymet->sum('cash_price'),
        'card_price' => $ClinetPaymet->sum('card_price'),
        'transfer_price' => $ClinetPaymet->sum('transfer_price'),
        'id_data' => $DailyRepotClientData->toArray()
    ];
});


// Route::group(['prefix' => 'branch'], function () {
//     Route::get('', [BranchController::class, 'index']);
//     Route::post('', [BranchController::class, 'store']);
//     Route::get('/{id}', [BranchController::class, 'show']);
//     Route::put('/{id}', [BranchController::class, 'update']);
//     Route::delete('/{id}', [BranchController::class, 'delete']);
// });



// Route::post('/webhook', [TelegramBotController::class, 'webhook']);
// Route::post('/webhook', [TelegramBotController::class, 'webhook']);
// Route::post('/webhook', [TelegramController::class, 'webhookHandler']);
// Route::get('/telegram/set-webhook', [TelegramBotController::class, 'setWebhook']);
