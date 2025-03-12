<?php

use App\Models\Client;
use App\Models\Master;
use App\Models\Order;
use App\Models\SendGroupOrder;
use App\Models\TgGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

function sendSMS($phone, $name, $clinicName, $clintId, $resultDomain, $key, $deviceId)
{
    // $key = 'c80c0a631e5ed59b5fb0eed6d455357899de4e91';
    $message = "Xurmatli  name  sizning  name 
 ga topshirgan tahlil natijalaringiz tayyor! Yuklab olish:
 name";
//     $message = "Xurmatli $name sizning $clinicName
//  ga topshirgan tahlil natijalaringiz tayyor! Yuklab olish:
//  $resultDomain?clint_id=$clintId";
    $type = 'sms';
    $prioritize = 1;
    $devices = $deviceId;
    // $devices = "1055";
    $url = "https://smsapp.uz/new/services/send.php";
    $response = Http::withoutVerifying()->get($url, [
        'number' => "+998$phone",
        'key' => $key,
        'message' => $message,
        'devices' => $devices,
        'type' => $type,
        'prioritize' => $prioritize,
    ]);
    if ($response->successful()) {
        var_dump($response->json()); // Agar JSON formatida bo'lsa
    } else {
        return response()->json(['error' => 'Xatolik yuz berdi'], 500);
    }
    // /devices

}
function oldGetWorkedTimeInSeconds($startTime)
{
    $start = Carbon::createFromFormat('H:i:s', $startTime);
    // Hozirgi vaqtni olish
    $now = Carbon::now();

    // Farqni sekundlarda olish
    $workedSeconds = $now->diffInSeconds($start);

    return $workedSeconds;
}
function passwordCheck($data, $password)
{
    foreach ($data as $item) {
        $passwords = Hash::check($password, $item->password) ?? false;
        if ($passwords) {
            return $item;
        }
    }
    return false;
}

function generateId()
{
    $lastRecord = Client::whereNull('parent_id')
        ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
        ->get()->max('person_id');
    return ($lastRecord ?? 0) + 1;
}
function generateProbirkaId()
{
    $lastRecord = Client::whereNotNull('parent_id')
        ->whereMonth('created_at', Carbon::now()->month)
        ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
        ->get()->max('probirka_id');
    return ($lastRecord ?? 0) + 1;
}

function requestOrder()
{
    $order = request()->get('order', '-id');
    if ($order[0] == '-') {
        $result = [
            'key' => substr($order, 1),
            'value' => 'desc',
        ];
    } else {
        $result = [
            'key' => $order,
            'value' => 'asc',
        ];
    }
    return $result;
}

function filterPhone($phone)
{
    return str_replace(['(', ')', ' ', '-'], '', $phone);
}

function uploadFile($file, $path, $old = null, $fileName = null): ?string
{
    $result = null;
    deleteFile($old);
    if ($file != null) {
        $names = explode(".", $file->getClientOriginalName());
        $model = ($fileName != null ? $fileName : time() . uniqid()) . '.' . $names[count($names) - 1];
        $file->storeAs("public/$path", $model);
        $result = "/storage/$path/" . $model;
    }
    return $result;
}

function deleteFile($path): void
{
    if ($path != null && file_exists(public_path() . $path)) {
        unlink(public_path() . $path);
    }
}

function nudePhone($phone)
{
    if (strlen($phone) > 0) {
        $phone = str_replace(['(', ')', ' ', '-', '+'], '', $phone);
    }

    if (strlen($phone) > 0) {
        if ($phone[0] == '7') {
            $phone = substr($phone, 1);
        }
    }
    return $phone;
}

function buildPhone($phone): string
{
    $phone = nudePhone($phone);
    return '+7 ' . '(' . substr($phone, 0, 3) . ') '
        . substr($phone, 3, 3) . '-'
        . substr($phone, 6, 2) . '-'
        . substr($phone, 8, 2);
}

function getKey()
{
    $key = explode('.', request()->route()->getName());
    array_pop($key);
    $key = implode('.', $key);
    return $key;
}

function getRequest($request = null)
{
    return $request ?? request();
}

// function defaultLocale()
// {
//     return Language::where('default', true)->first();
// }

// function allLanguage()
// {
//     return Language::all();
// }

function defaultLocaleCode()
{
    // return optional(defaultLocale())->url;
}

function paginate($items, $perPage = 15, $page = null, $options = [])
{
    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

    $items = $items instanceof Collection ? $items : Collection::make($items);

    return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
}

function sendTelegramNotification($text, $chat_id = '-1001895652594')
{
    $query = http_build_query([
        'parse' => 'html',
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_markup' => ['remove_keyboard'],
    ]);
    $url = "https://api.telegram.org/bot" . config('telegram.token') . "/sendMessage?" . $query;
    $ch = curl_init();
    $optArray = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
    );
    curl_setopt_array($ch, $optArray);
    $result = curl_exec($ch);
    curl_close($ch);
}

function tgGroupSend($data)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $pinChatMessageUrl = "https://api.telegram.org/bot$token/pinChatMessage";
    $text = messageData($data, $data->status);
    $messageText = "🆘🆘🆘 YANGI BUYURTMA  🆘🆘🆘 \n\n";
    $messageText .= "🆔 Buyurtma №$data->id — #aktiv  \n\n";
    $messageText .= "📸 Soni:  $data->qty dona \n";
    $messageText .= "💰 Xizmat haqi: " . $data->qty . "x" . $data->master_salary . "=" . $data->master_salary * $data->qty . " $ \n";
    // $messageText .= "💰 Narxi : $data->master_salary $ \n";
    $messageText .= "📍 Manzil:  $data->target_adress \n";

    // Make an HTTP POST request to the Telegram API

    $inlineKeyboard = [
        'inline_keyboard' => [
            [
                [
                    'text' => '📥 BUYURTMANI OLISH',
                    // 'callback_data' => 'order_' . $data->id
                    'url' => "https://t.me/" . botName() . "?start=$data->id"
                ], //
            ],
        ],
    ];
    $tgGroup = TgGroup::where('is_send', 1)->get();
    foreach ($tgGroup as $item) {
        $res = Http::post($url, [
            'chat_id' => $item->tg_id,
            'text' => $text, // Message with emoji and markdown
            // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
            'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
        ]);
        $messageId = $res->json()['result']['message_id'];
        SendGroupOrder::create([

            'msg_id' => $messageId,
            'order_id' => $data->id,
            'chat_id' => $item->tg_id,
            'group_id' => $item->id,

        ]);
        Http::post($pinChatMessageUrl, [
            'chat_id' => $item->tg_id,
            'message_id' => $messageId,
        ]);
    }
}
function tgGroupSendEdit($data)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/editMessageText";
    $sendurl = "https://api.telegram.org/bot$token/sendMessage";
    $pinChatMessageUrl = 'https://api.telegram.org/bot$token/pinChatMessage';
    $master = Master::find($data->master_id);
    $inlineKeyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🆗 YAKUNLASH', 'callback_data' => 'finish_' . $data->id], //
            ],
        ],
    ];
    Log::info('callbackQuery: ' . '$data');

    if ($master) {
        Log::info('callbackQuery: ' . $master->full_name);
        $messageGroup = messageData($data, $data->status . "_group");
        $message = messageData($data, $data->status . "_edit");
        Http::post($sendurl, [
            'chat_id' => $master->tg_id,
            'text' => $message,
        ]);
        $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();
        foreach ($SendGroupOrder as $key => $item) {
            # code...
            if ($data->status == 'do_work') {
                Http::post($url, [
                    'chat_id' => $item->chat_id,
                    'text' => $messageGroup,
                    'message_id' => $item->msg_id, // Message with emoji and markdown
                    'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
                ]);
            } else {
                Http::post($url, [
                    'chat_id' => $item->chat_id,
                    'text' => $messageGroup,
                    'message_id' => $item->msg_id, // Message with emoji and markdown
                ]);
            }
        }
    } else {
        $text = messageData($data, 'ether');
        $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();
        Log::info('callbackQuery: ' . $data->full_name);
        Log::info('text: ' . $text);
        $inlineKeyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '📥 BUYURTMANI OLISH',
                        // 'callback_data' => 'order_' . $data->id
                        'url' => "https://t.me/" . botName() . "?start=$data->id"
                    ], //
                ],
            ],
        ];
        foreach ($SendGroupOrder as $key => $item) {
            # code...
            Http::post($url, [
                'chat_id' => $item->chat_id,
                'text' => $text,
                'message_id' => $item->msg_id, // Message with emoji and markdown
                'reply_markup' => json_encode($inlineKeyboard)
            ]);
        }
    }
}
function freeze($data)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/editMessageText";
    $sendMessageUrl = "https://api.telegram.org/bot$token/sendMessage";
    if ($data->status == 'ether_freeze') {
        $message = messageData($data, 'ether_freeze');
        $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();
        foreach ($SendGroupOrder as $key => $item) {
            # code...
            Http::post($url, [
                'chat_id' => $item->chat_id,
                'text' => $message,
                'message_id' => $item->msg_id, // Message with emoji and markdown
            ]);
            SendGroupOrder::find($item->id)->delete();
        }
        return;
    }
    if ($data->status == 'master_freeze' || $data->status == 'take_order') {
        $message_group = messageData($data, $data->status . "_group");
        $message = messageData($data, $data->status);
        $master = Master::find($data->master_id);



        Http::post($sendMessageUrl, [
            'chat_id' => $master->tg_id,
            'text' => $message,
        ]);
        $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();
        foreach ($SendGroupOrder as $key => $item) {
            # code...
            Http::post($url, [
                'chat_id' => $item->chat_id,
                'text' => $message_group,
                'message_id' => $item->msg_id, // Message with emoji and markdown
            ]);
        }
        return;
    }
    tgGroupSend($data);
    return;
}
function masterReset($data, $new = true)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/editMessageText";
    $sendurl = "https://api.telegram.org/bot$token/sendMessage";
    if (+$data->master_id > 0) {
        $message_group = messageData($data, "master_cancel_group");
        $message = messageData($data, 'master_cancel');
        $master = Master::find($data->master_id);
        Http::post($sendurl, [
            'chat_id' => $master->tg_id,
            'text' => $message,
        ]);
        $master->update([
            'status' => 'active'
        ]);
        $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();
        foreach ($SendGroupOrder as $key => $item) {
            # code...
            Http::post($url, [
                'chat_id' => $item->chat_id,
                'text' => $message_group,
                'message_id' => $item->msg_id, // Message with emoji and markdown
            ]);
            SendGroupOrder::find($item->id)->delete();
        }
        if ($new) {
            $res =  Order::find($data->id)->update([
                'status' => 'ether',
                'master_id' => '0',

            ]);
            tgGroupSend(Order::find($data->id));
        }
    } else {
        $message = messageData($data, 'ether_freeze');
        $new_message = messageData($data, 'ether');
        $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();
        foreach ($SendGroupOrder as $key => $item) {
            # code...
            Http::post($url, [
                'chat_id' => $item->chat_id,
                'text' => $message,
                'message_id' => $item->msg_id, // Message with emoji and markdown
            ]);
            SendGroupOrder::find($item->id)->delete();
        }
        if ($new) {
            $res =  Order::find($data->id)->update([
                'status' => 'ether'
            ]);
            tgGroupSend(Order::find($data->id));
        }
    }
    return;
}
function orderReset($data)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/editMessageText";
    $sendurl = "https://api.telegram.org/bot$token/sendMessage";
    $pinChatMessageUrl = "https://api.telegram.org/bot$token/pinChatMessage";
    $messageTextBekor = "🆔 Buyurtma №$data->id — #bekor_qilindi  \n\n";
    $messageTextActiv = "🆔 Buyurtma №$data->id — #aktiv  \n\n";
    $messageTextTarget = '';
    $messageTextTarget .= "📸 Soni:  $data->qty dona \n";
    // $messageTextTarget .= "💰 Narxi : $data->master_salary $ \n";
    $messageTextTarget .= "💰 Xizmat haqi: " . $data->qty . "x" . $data->master_salary . "=" . $data->master_salary * $data->qty . " $ \n";
    $messageTextTarget .= "📍 Manzil:  $data->target_adress \n\n";
    $messageTextBekor .= $messageTextTarget;
    $messageTextActiv .= $messageTextTarget;
    $master = Master::find($data->master_id);
    // $messageTextBekor .= "@$master->username Buyurtma qabul qilindi! @kameradokon_bot botga oting!";
    $messageTextBekor .= "🆘🆘🆘@$master->username Buyurtma qabul qilindi!🆘🆘🆘 \n @" . botName() . " botga oting!";
    // Make an HTTP POST request to the Telegram API
    $inlineKeyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📥 BUYURTMANI OLISH', 'callback_data' => 'order_' . $data->id], //
            ],
        ],
    ];
    $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();

    foreach ($SendGroupOrder as $key => $item) {
        # code...
        Http::post($url, [
            'chat_id' => $item->chat_id,
            'text' => $messageTextBekor,
            'message_id' => $item->msg_id, // Message with emoji and markdown
            // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
            // 'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
        ]);
        SendGroupOrder::find($item->id)->delete();
    }
    $tgGroup = TgGroup::where('is_send', 1)->get();
    Order::find($data->id)->update([
        'master_id' => 0,
    ]);
    foreach ($tgGroup as $item) {
        $res = Http::post($sendurl, [
            'chat_id' => $item->tg_id,
            'text' => $messageTextActiv, // Message with emoji and markdown
            // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
            'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
        ]);
        $messageId = $res->json()['result']['message_id'];
        SendGroupOrder::create([

            'msg_id' => $messageId,
            'order_id' => $data->id,
            'chat_id' => $item->tg_id,
            'group_id' => $item->id,

        ]);
        Http::post($pinChatMessageUrl, [
            'chat_id' => $item->tg_id,
            'message_id' => $messageId,
        ]);
    }
}
function masterPay($mastertgid, $orderId)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/sendMessage";
    // $master = Master::where('id', $data->master_id)->first();
    Http::post($url, [
        'chat_id' => $mastertgid,
        'text' => "✅ Buyurtma №$orderId uchun to'lov qilindi. \n💲Balansingizni tekshiringiz mumkin!",
    ]);
}
function tgGroupSendNot($messageId, $data)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/editMessageText";
    $messageText = "🆔 Buyurtma №$data->id — #aktiv  \n\n";
    $messageText .= "📸 Soni:  $data->qty dona \n";
    // $messageText .= "💰 Narxi : $data->master_salary $ \n";
    $messageText .= "💰 Xizmat haqi: " . $data->qty . "x" . $data->master_salary . "=" . $data->master_salary * $data->qty . " $ \n";
    $messageText .= "📍 Manzil:  $data->target_adress \n";

    // Make an HTTP POST request to the Telegram API
    $inlineKeyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📥 BUYURTMANI OLISH', 'callback_data' => 'order_' . $data->id], //
            ],
        ],
    ];
    $tgGroup = TgGroup::where('is_send', 1)->get();
    foreach ($tgGroup as $item) {
        $res = Http::post($url, [
            'chat_id' => $item->tg_id,
            'text' => $messageText, // Message with emoji and markdown
            'message_id' => $messageId,
            // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
            'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
        ]);
        $id = $res->json()['result']['message_id'];
    }
}
function sendMaster($chatId, $data)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $messageText = "*🆔 Buyurtma: $data->id *\n\n";
    $messageText .= "📸 `Soni: ` " . $data->qty . "\n";
    $messageText .= "💰 `Hizmat narxi: ` " . $data->master_salary . "\n";
    $messageText .= "📍 `Mijoz raqami: ` +998" . $data->phone . "\n";
    $messageText .= "📍 `Manzil: ` " . $data->address . "\n";

    // Make an HTTP POST request to the Telegram API
    $inlineKeyboard = [
        'inline_keyboard' => [
            [
                ['text' => 'Buyermani 🆗 YAKUNLASH', 'callback_data' => 'order_' . $data->id], //
            ],
        ],
    ];

    Http::post($url, [
        'chat_id' => $chatId,
        'text' => $messageText, // Message with emoji and markdown
        'parse_mode' => 'Markdown', // Enable markdown for text formatting
        'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
    ]);
}
function sendMasterCheck($chatId, $data)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $messageText = "*🆔 Buyurtma: $data->id *\n\n";
    $messageText .= "📸 `Soni: ` " . $data->qty . "\n";
    $messageText .= "💰 `Hizmat narxi: ` " . $data->master_salary . "\n";
    $messageText .= "📍 `Mijoz raqami: ` +998" . $data->phone . "\n";
    $messageText .= "📍 `Manzil: ` " . $data->address . "\n";

    // Make an HTTP POST request to the Telegram API
    $inlineKeyboard = [
        'inline_keyboard' => [
            [
                ['text' => 'Buyermani 🆗 YAKUNLASH', 'callback_data' => 'order_' . $data->id], //
            ],
        ],
    ];

    Http::post($url, [
        'chat_id' => $chatId,
        'text' => " Buyurtma  $data->id  tekshirildi. pulni olishingiz mumkin!", // Message with emoji and markdown

    ]);
}

function groupOrdercheck($data)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/editMessageText";
    $url2 = "https://api.telegram.org/bot$token/sendMessage";

    // $messageText = "*📦 Buyurtma: $data->id *\n\n";
    $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();
    $master = Master::where('id', $data->master_id)->first();
    $pay_finally_group_text = messageData($data, 'pay_finally_group');
    $pay_finally_text = messageData($data, 'pay_finally');

    Http::post($url2, [
        'chat_id' => $master->tg_id,
        'text' => $pay_finally_text,
    ]);
    foreach ($SendGroupOrder as $key => $item) {
        # code...
        Http::post($url, [
            'chat_id' => $item->chat_id,
            'text' => $pay_finally_group_text,
            'message_id' => $item->msg_id, // Message with emoji and markdown
            // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
            // 'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
        ]);
    }
}

function installation_time($data)
{
    $token = tg_token();
    $master = Master::find($data->master_id);
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $message = messageData($data, 'do_work');
    Log::info('callbackQuery: ' . $message);
    $inlineKeyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🆗 YAKUNLASH', 'callback_data' => 'finish_' . $data->id], //
            ],
        ],
    ];
    Http::post($url, [
        'chat_id' => $master->tg_id,
        'text' => $message,
        'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
    ]);
}

function leaveChat($groupId)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/leaveChat";
    Http::post($url, [
        'chat_id' => $groupId,
    ]);
}

function soketSend($channel, $data)
{
    $url = 'https://soket6.akrampulatov.uz/api/soket';
    Http::post($url, [
        'data' => $data,
        'channel' => $channel,
    ]);
}

function orderDelete($data, $new = true)
{
    $token = tg_token();
    $url = "https://api.telegram.org/bot$token/editMessageText";
    $sendurl = "https://api.telegram.org/bot$token/sendMessage";
    $messageTextBekor = "🆘🆘🆘 BEKOR QILINDI  🆘🆘🆘 \n\n";
    $messageTextBekor .= "🆔 Buyurtma №$data->id — #bekor_qilindi  \n\n";

    $messageTextActiv = "🆔 Buyurtma №$data->id — #aktiv  \n\n";
    $messageTextTarget = '';

    $messageTextTarget .= "📸 Soni:  $data->qty dona \n";
    // $messageTextTarget .= "💰 Narxi : $data->master_salary $ \n";
    $messageTextTarget .= "💰 Xizmat haqi: " . $data->qty . "x" . $data->master_salary . "=" . $data->master_salary * $data->qty . " $ \n";
    $messageTextTarget .= "📍 Manzil:  $data->target_adress \n\n";
    $messageTextBekor .= $messageTextTarget;
    $messageTextActiv .= $messageTextTarget;
    $master = Master::find($data->master_id);
    if ($master) {
        $master->update([
            'is_occupied' => 0,
        ]);
        $messageTextBekor .= "🆘🆘🆘 Ogohlantirish:  🆘🆘🆘 \n\n";
        $messageTextBekor .= "@$master->username Buyurtma qabul qilindi! \n\n @" . botName() . " botga oting!";
    }
    // Make an HTTP POST request to the Telegram API

    $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();

    foreach ($SendGroupOrder as $key => $item) {
        # code...
        Http::post($url, [
            'chat_id' => $item->chat_id,
            'text' => $messageTextBekor,
            'message_id' => $item->msg_id, // Message with emoji and markdown
            // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
            // 'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
        ]);
        SendGroupOrder::find($item->id)->delete();
    }
    if ($master) {
        $master->update([
            'is_occupied' => 0,
        ]);
        Http::post($sendurl, [
            'chat_id' => $master->tg_id,
            'text' => "🆘🆘🆘 BEKOR QILINDI  🆘🆘🆘   \n\n🆔 Buyurtma №$data->id — #bekor_qilindi  \n\n  @$master->username boshqa buyurtma olishingiz mumkin.", // Message with emoji and markdown
            // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
        ]);
    }
}


function masterActive($chatId, $data)
{
    $token = tg_token();
    $sendurl = "https://api.telegram.org/bot$token/sendMessage";
    $messageText = '';
    if ($data) {
        $messageText = '✅ Siz bilan muvaffaqiyatli shartnoma tuzildi!';
    } else {
        $messageText = '🛑 Siz bilan  shartnoma bekor qilindi!';
    }
    Http::post($sendurl, [
        'chat_id' => $chatId,
        'text' => $messageText,
    ]);
}

// token
function tg_token()
{
    return '7740457129:AAH7UeqcNBJXMfk-9OcBeD8yOmPt33ufSc8';
}
function ogohlantish()
{
    return '⚠️ OGOHLANTIRISH⚠️';
}
function tahrirlandi()
{
    return '✏️ TAHRIRLANDI ✏️';
}
function bajarilmoqda()
{
    return '🔧 BAJARILMOQDA 🔧';
}

function tekshirilmoqda()
{
    return '🔄 TEKSHIRILMOQDA 🔄';
}
function yakunlandi()
{
    return '✅ YAKUNLANDI ✅';
}
function v_toxtatildi()
{
    return '⏳ VAQTINCHALIK TOXTATILDI⏳';
}
function y_buyurtma()
{
    return '💰YANGI BUYURTMA💰';
}
function b_qilindi()
{
    return '🚫 BEKOR QILINDI 🚫';
}
function b_olindi()
{
    return '✅  BEKOR QILINDI ✅ ';
}
function yakunlash()
{
    return '🆗 YAKUNLASH';
}
function b_olish()
{
    return '📥 BUYURTMANI OLISH';
}
function buyurtma()
{
    return '🆔 Buyurtma №';
}
function messageData($order, $status)
{
    $text = '';
    // 'full_name',
    //     'address',
    //     'price',
    //     'qty',
    //     'master_id',
    //     'master_salary',
    //     'is_check',
    //     'is_finish',
    //     'master_salary_pay',
    //     'phone',
    //     'operator_id',
    //     'finish_date',
    //     'customer_id',
    //     'installation_time',
    //     'is_freeze',
    //     'target_adress',
    //     'warranty_period_type', //// day,week,month,year
    //     'warranty_period_quantity', //
    //     'warranty_period_date', //
    //     'is_installation_time', //
    // 'full_ 


    switch ($status) {
        case 'ether': {
                $text = y_buyurtma() . "\n\n"; /// buyurtma berilsa guruhga chiqadi
                $text .= "🆔 Buyurtma №:" . $order->id . "—#aktiv  \n\n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "📍 Mo'ljal:  $order->target_adress \n\n";
                return $text;
            }
        case 'ether_freeze': {
                $text = b_qilindi() . "\n\n"; /// buyurtma berilsa guruhga chiqadi
                $text .= "🆔 Buyurtma №:" . $order->id . "—#bekor_qilindi  \n\n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "📍 Mo'ljal:  $order->target_adress \n\n";
                return $text;
            }
        case 'take_order': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text =  "👌BUYURTMA OLINDI👌 \n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#bajarilmoqda  \n\n";
                $text .= "🧑‍💼 MIJOZ:  $order->full_name \n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "📞 Telefon: +998$order->phone \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "📍 Manzil:  $order->target_adress. $order->address  \n\n";
                $text .= ogohlantish() . "\n\n";
                $text .= "📌Mijozga telefon qiling va  o'rnatish vaqtini kelishib oling.   \n";
                $text .= "📌O'rnatish vaqtini adminga xabar bering.   \n";
                $text .= "📌Admin o'rnatish vaqtini kiritadi va buyurtmani yakunlash huquqiga ega bo'lasiz.   \n";
                $text .= "📌Agar mijoz bilan telefon orqali bog'lana olmasangiz, admin buyurtmani muzlatib qo'yishi mumkin.  \n";
                $text .= "📌Shunda - siz yangi buyurtmani ola olasiz. \n";
                return  $text;
            }
        case 'take_order_edit': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = tahrirlandi() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#bajarilmoqda  \n\n";
                $text .= "🧑‍💼 MIJOZ:  $order->full_name \n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "📞 Telefon: +998$order->phone \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "📍 Manzil:  $order->target_adress. $order->address  \n\n";
                $text .= ogohlantish() . "\n\n";
                $text .= "📌Mijozga telefon qiling va  o'rnatish vaqtini kelishib oling.   \n";
                $text .= "📌O'rnatish vaqtini adminga xabar bering.   \n";
                $text .= "📌Admin o'rnatish vaqtini kiritadi va buyurtmani yakunlash huquqiga ega bo'lasiz.   \n";
                $text .= "📌Agar mijoz bilan telefon orqali bog'lana olmasangiz, admin buyurtmani muzlatib qo'yishi mumkin.  \n";
                $text .= "📌Shunda - siz yangi buyurtmani ola olasiz. \n";
                return  $text;
            }
        case 'do_work': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = bajarilmoqda() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#bajarilmoqda  \n\n";
                $text .= "🧑‍💼 MIJOZ:  $order->full_name \n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "📞 Telefon: +998$order->phone \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "⏰ O'rnatish vaqti: $order->installation_time \n";
                $text .= "📍 Manzil:  $order->target_adress. $order->address  \n\n";
                $text .= "⚠️ @$master->username buyurtma sizga topshirildi.\n";
                $text .= "☑️Buyurtmani tugatsangiz 'Yakunlash' tugamasini bosing.\n";
                return  $text;
            }
        case 'do_work_edit': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = tahrirlandi() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#bajarilmoqda  \n\n";
                $text .= "🧑‍💼 MIJOZ:  $order->full_name \n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "📞 Telefon: +998$order->phone \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "⏰ O'rnatish vaqti: $order->installation_time \n";
                $text .= "📍 Manzil:  $order->target_adress. $order->address  \n\n";
                $text .= "⚠️ @$master->username buyurtma sizga topshirildi.\n";
                $text .= "☑️Buyurtmani tugatsangiz 'Yakunlash' tugamasini bosing.\n";
                return  $text;
            }
        case 'master_freeze': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = v_toxtatildi() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#vaqtinchalik_to'xtatildi  \n\n";
                $text .= "🧑‍💼 MIJOZ:  $order->full_name \n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "📞 Telefon: +998$order->phone \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "⏰ O'rnatish vaqti: $order->installation_time \n";
                $text .= "📍 Manzil:  $order->target_adress. $order->address  \n\n";
                return  $text;
            }
        case 'master_cancel': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = b_qilindi() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#bekor_qilindi  \n\n";
                $text .= "🧑‍💼 MIJOZ:  $order->full_name \n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "📞 Telefon: +998$order->phone \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "⏰ O'rnatish vaqti: $order->installation_time \n";
                $text .= "📍 Manzil:  $order->target_adress. $order->address  \n\n";
                return  $text;
            }
        case 'finish': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = tekshirilmoqda() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#tekshirilmoqda  \n\n";
                $text .= "🧑‍💼 MIJOZ:  $order->full_name \n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "📞 Telefon: +998$order->phone \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "⏰ O'rnatish vaqti: $order->installation_time \n";
                $text .= "📍 Manzil:  $order->target_adress. $order->address  \n\n";
                $text .= ogohlantish() . "\n\n🆘 ADMIN buyurtmani tekshirishini kuting.\n🆘 Agar tekshirish vaqti uzoq muddat talab qilsa, admin bilan bog'laning!";
                // $text .= ogohlantish() . "\n\n @$master->username ⚠️ @akrampulatov buyurtma tekshirildi.  \n\n 💲Xizmat haqqingiz  va yangi buyurtmani olishingiz mumkin!";
                return  $text;
            }
        case 'pay_finally': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = yakunlandi() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#yakunlandi  \n\n";
                $text .= "🧑‍💼 MIJOZ:  $order->full_name \n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "📞 Telefon: +998$order->phone \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "⏰ O'rnatish vaqti: $order->installation_time \n";
                $text .= "📍 Manzil:  $order->target_adress. $order->address  \n\n";
                $text .= ogohlantish() . "\n\n ⚠️ @$master->username buyurtma tekshirildi.  \n\n 💲Xizmat haqqingiz  va yangi buyurtmani olishingiz mumkin!";
                return  $text;
            }
        case 'finally': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = yakunlandi() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#yakunlandi  \n\n";
                $text .= "🧑‍💼 MIJOZ:  $order->full_name \n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "📞 Telefon: +998$order->phone \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "⏰ O'rnatish vaqti: $order->installation_time \n";
                $text .= "📍 Manzil:  $order->target_adress. $order->address  \n\n";
                // $text .= ogohlantish() . "\n\n @$master->username ⚠️ @$master->username buyurtma tekshirildi.  \n\n 💲Xizmat haqqingiz  va yangi buyurtmani olishingiz mumkin!";
                return  $text;
            }
        case 'pay_finally_group': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = yakunlandi() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#yakunlandi  \n\n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "📍 Mo'ljal:  $order->target_adress \n\n";
                $text .= ogohlantish() . "\n\n @$master->username Buyurtma qabul qilindi!  \n\n @" . botName() . " botga oting!";
                return  $text;
            }
        case 'finish_group': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = tekshirilmoqda() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#tekshirilmoqda  \n\n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "📍 Mo'ljal:  $order->target_adress \n\n";
                $text .= ogohlantish() . "\n\n @$master->username Buyurtma qabul qilindi!  \n\n @" . botName() . " botga oting!";
                return  $text;
            }
        case 'master_cancel_group': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = b_qilindi() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#bekor_qilindi  \n\n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "📍 Mo'ljal:  $order->target_adress \n\n";
                $text .= ogohlantish() . "\n\n @$master->username Buyurtma qabul qilindi!  \n\n @" . botName() . " botga oting!";
                return  $text;
            }
        case 'take_order_group': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = bajarilmoqda() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#bajarilmoqda  \n\n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "📍 Mo'ljal:  $order->target_adress \n\n";
                $text .= ogohlantish() . "\n\n @$master->username Buyurtma qabul qilindi!  \n\n @" . botName() . " botga oting!";
                return  $text;
            }
        case 'master_freeze_group': //// buyurtma olganda guruhga 
            {
                $master = Master::find($order->master_id);
                $text = v_toxtatildi() . "\n\n";
                $text .= "🆔 Buyurtma №:" . $order->id . "—#vaqtinchalik_to'xtatildi  \n\n";
                $text .= "📸 Soni:  $order->qty dona \n";
                $text .= "💬 Izoh:  $order->comment \n";
                $text .= "💰 Xizmat haqi: " . $order->qty . "x" . $order->master_salary . "=" . $order->master_salary * $order->qty . " $ \n";
                $text .= "📍 Mo'ljal:  $order->target_adress \n\n";
                $text .= ogohlantish() . "\n\n @$master->username Buyurtma qabul qilindi!  \n\n @" . botName() . " botga oting!";
                return  $text;
            }
        case 'balans': //// buyurtma qabul qildimni bossa ustaga boradi
            $order1 = Order::where('master_id', $order->id)
                ->whereIn('status', ['pay_finally', 'finally'])
                ->get();

            $balance = $order1->where('status', 'pay_finally')->sum(function ($q) {
                return $q->master_salary * $q->qty - $q->master_salary_pay * $q->qty;
            });
            $text = "💲BALANS💲 \n\n";
            $text .= "👤Usta: $order->full_name \n";
            $text .= "📞 Telefon: $order->phone \n";
            $text .= "💰 Balans:  $balance $\n";
            $text .= "✅ Bajarilgan buyurtmalar soni: " . $order1->count() . " \n";
            return $text;
        case 'none': //// buyurtma qabul qildimni bossa ustaga boradi
            {
                $text = "❌ Ma'lumot mavjud emas! ";
                return $text;
            }
        case 'send_phone_q': //// buyurtma qabul qildimni bossa ustaga boradi
            {
                $text = "@$order Telefon raqamingizni jo'nating:";
                return $text;
            }
        case 'we_work': //// buyurtma qabul qildimni bossa ustaga boradi
            {
                $text = "@$order Biz bilan ishlashga tayyormisiz?:";
                return $text;
            }
        case 'ok': //// buyurtma qabul qildimni bossa ustaga boradi
            {
                $text = "@$order 📤 Sizning so`rovingiz adminga jo`natildi.";
                return $text;
            }
        case 'is_active': //// buyurtma qabul qildimni bossa ustaga boradi
            {
                $text = ogohlantish() . " \n\n Usta:@$order \n\n Buyurtmani  olish uchun   Admin bilan rasmiy shartnomani tuzing!";
                return $text;
            }

        case 'is_occupied': //// buyurtma qabul qildimni bossa ustaga boradi
            {
                $text = ogohlantish() . " \n\n Usta:@$order \n\n Buyurtmani qabul qila olmaysiz! \n\n Agar buyurtma bajarilayotgan bo'lsa.";
                return $text;
            }
        case 'order_received': //// buyurtma qabul qildimni bossa ustaga boradi
            {
                $master = Master::find($order->master_id);
                $text = ogohlantish() . " \n\n Usta:@$master->username \n\n🆔 Buyurtma №$order->id olingan!";
                return $text;
            }
        default:
            # code...
            break;
    }
}

function soketChannel()
{
    return '';
    // return '.usta-bot';
}
function botName()
{
    return 'kameradokon_bot';
}


// function createDatabaseBackup()
// { $dbName = env('DB_DATABASE');
//     $backupFile = 'db-backup-' . date('Y-m-d_H-i-s') . '.sql';
//     $backupPath = storage_path('app/' . $backupFile);

//     // PDO ulanishini olish
//     $pdo = DB::connection()->getPdo();

//     // Barcha jadvallarni olish
//     $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

//     $sqlDump = '';

//     foreach ($tables as $table) {
//         // CREATE TABLE so‘zini qo‘shish
//         $createTable = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC)['Create Table'];
//         $sqlDump .= "\n\n$createTable;\n";

//         // Har bir jadval uchun INSERT ma'lumotlarini qo‘shish
//         $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
//         foreach ($rows as $row) {
//             $values = array_map(fn($value) => $pdo->quote($value), $row);
//             $sqlDump .= "INSERT INTO $table VALUES (" . implode(',', $values) . ");\n";
//         }
//         $sqlDump .= "\n\n";
//     }

//     // Faylni saqlash
//     file_put_contents($backupPath, $sqlDump);

//     return sendFileToTelegram($backupFile);
// }


// function sendFileToTelegram($filePath)
// {
//     $telegramToken = '8099217057:AAEvlsdrolAvormhVegtY0ccXF852WnjLFM'; // Telegram bot tokeningiz
//     $chatId = '1082454723'; // Bot bilan gaplashayotgan chat ID
//     $backupPath = storage_path('app/' . $filePath);

//     // To'g'ridan-to'g'ri ushbu manzilda faylni oching
//     $response = Http::attach(
//         'document', file_get_contents($backupPath), basename($backupPath)
//     )->post("https://api.telegram.org/bot{$telegramToken}/sendDocument", [
//         'chat_id' => $chatId,
//     ]);

//     if ($response->successful()) {
//         return 'File successfully sent to Telegram!';
//     } else {
//         return 'Failed to send the file. Error: ' . $response->body();
//     }
// }
function createDatabaseBackupAndSendToTelegram()
{
    $dbName = env('DB_DATABASE');
    $telegramToken = '8099217057:AAEvlsdrolAvormhVegtY0ccXF852WnjLFM'; // Telegram bot tokeningiz
    $chatId = '1082454723'; // Bot bilan gaplashayotgan chat ID

    // PDO ulanishini olish
    $pdo = DB::connection()->getPdo();

    // Barcha jadvallarni olish
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    $sqlDump = '';

    foreach ($tables as $table) {
        // CREATE TABLE so‘zini qo‘shish
        $createTable = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC)['Create Table'];
        $sqlDump .= "\n\n$createTable;\n";

        // Har bir jadval uchun INSERT ma'lumotlarini qo‘shish
        $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $values = array_map(fn($value) => $pdo->quote($value), $row);
            $sqlDump .= "INSERT INTO $table VALUES (" . implode(',', $values) . ");\n";
        }
        $sqlDump .= "\n\n";
    }

    // Telegram botga faylni yuborish
    $response = Http::attach(
        'document',
        $sqlDump,
        $dbName . '-backup-' . date('Y-m-d_H-i-s') . '.sql'
    )->post("https://api.telegram.org/bot{$telegramToken}/sendDocument", [
        'chat_id' => $chatId,
    ]);

    if ($response->successful()) {
        return 'Database backup successfully sent to Telegram!';
    } else {
        return 'Failed to send the backup to Telegram. Error: ' . $response->body();
    }
}
