<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Master\MasterResource;
use App\Http\Resources\Order\OrderResource;
use App\Models\Master;
use App\Models\Order;
use App\Models\SendGroupOrder;
use App\Models\TgBotConnect;
use App\Models\TgGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{

    // Handle incoming webhook updates
    public function webhook(Request $request)
    {
        // Parse incoming message data from Telegram
        try {
            //code...
            $update = $request->all();
            $message = $update['message']['text'] ?? '';
            $chatId = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? '';
            $parts = explode(' ', $message);
            $master = Master::where(['tg_id' => $chatId])->first();
            if (isset($update['my_chat_member'])) {
                $chat = $update['my_chat_member']['chat'];
                $newStatus = $update['my_chat_member']['new_chat_member']['status'];

                // Check if the bot has joined a group or supergroup and the new status is 'member'
                if (($chat['type'] === 'group' || $chat['type'] === 'supergroup') && $newStatus === 'member') {
                    $chatId = $chat['id']; // Group ID

                    // Log the group ID
                    Log::info('Bot joined group with ID: ' . $chatId);

                    // Retrieve or update the group title (if exists)
                    $title = $chat['title'] ?? null;

                    // Check if the group exists in the database
                    $condition = ['tg_id' => $chatId];

                    // Data to update or create
                    $data = [
                        'tg_id' => $chatId,
                        'title' => $title,
                    ];
            
                    TgGroup::updateOrCreate($condition, $data);
                    $tggroup = TgGroup::where('tg_id',$chatId)->first();
                    soketSend('tg_group'.soketChannel(), $tggroup);
                    return;
                } elseif ($newStatus === 'left' || $newStatus === 'kicked') {
                    // Log when the bot leaves the group
                    Log::info('Bot left or was kicked from group with ID: ' . $chat['id']);
                }
            }
            if ($master) {
                if (isset($update['message']['contact'])) {
                    $master = Master::where('tg_id', $chatId)->first();
                    $phoneNumber = $update['message']['contact']['phone_number'];
                    $first_name = $update['message']['chat']['first_name'];
                    $username = $update['message']['chat']['username'];
                    $master->update([
                        'phone' => $phoneNumber,
                        'status' => 'we_work',
                    ]);
                    return  $this->contract($chatId, messageData($username, $master->status));
                }
                if ($master->status == 'send_phone_q') {
                    return $this->requestPhoneNumber($chatId, messageData($master->username, $master->status));
                }
                if ($master->status == 'we_work') {
                    if ($message == '🆗') {
                        $master = Master::where('tg_id', $chatId)->first();
                        $master->update([
                            'is_contract' => 1,
                            'status' => 'is_active',
                        ]);
                        soketSend('master'.soketChannel(), new MasterResource($master));
                        return $this->homepage($chatId, messageData($master->username, 'ok'));
                    } else {
                        return $this->contract($chatId, messageData($master->username, $master->status));
                    }
                }
                if ($master->status == 'is_active' && (count($parts) == 2 && $parts[0] == '/start'  && +$parts[1] > 0)) {
                    return $this->homepage($chatId, messageData($master->username, 'is_active'));
                }
                if ($master->status == 'is_occupied' && (count($parts) == 2 && $parts[0] == '/start'  && +$parts[1] > 0)) {
                    return $this->homepage($chatId, messageData($master->username, 'is_occupied'));
                }
                if ($master->status == 'active' && (count($parts) == 2 && $parts[0] == '/start'  && +$parts[1] > 0)) {
                    $orderId = +$parts[1]; // Extract the order ID
                    $order = Order::where(['id' => $orderId])
                        // ->whereNull('master_id')
                        ->where('status', 'ether')
                        // ->orwhere('master_id', '0')
                        ->first();
                    if ($order) {
                        $order->update([
                            'master_id' => $master->id,
                            'status' => 'take_order'
                        ]);
                        $master->update([
                            'status' => 'is_occupied',
                        ]);
                        $this->takeOrder($order, $chatId);
                        soketSend('order'.soketChannel(), new OrderResource(Order::with('master')->find($order->id)));
                        return;
                    } else {
                        $this->homepage($chatId, messageData($order, 'order_received'));
                    }
                }
                if (isset($update['callback_query'])) {
                    $callbackQuery = $update['callback_query'];
                    $data = $callbackQuery['data'];
                    $parts = explode(' ', $data);
                    $messageId = $update['callback_query']['message']['message_id'];
                    if (strpos($data, 'doneactiveorder_target_') !== false) {
                        $orderId = str_replace('doneactiveorder_target_', '', $data); // Extract the order ID
                        return $this->doneTargetOrder($chatId, $orderId);
                    }
                    if (strpos($data, 'activeorder_target_') !== false) {
                        $orderId = str_replace('activeorder_target_', '', $data); // Extract the order ID
                        return  $this->targetOrder($chatId, $orderId);
                    }
                    if (strpos($data, 'finish_') !== false) {
                        $orderId = str_replace('finish_', '', $data); // Extract the order ID
                        $order = Order::where(['id' => $orderId, 'status' => 'do_work'])
                            ->first();
                        if ($order) {
                            // return $this->homepage($chatId, json_encode($order));
                            $order->update([
                                'status' => 'finish'
                            ]);
                            soketSend('order'.soketChannel(), new OrderResource(Order::with('master')->find($order->id)));
                            return $this->orderFinish($chatId, $order, $messageId);
                        } else {
                            return $this->homepage($chatId, messageData(1, 'none'));
                        }
                    }
                }



                switch ($message) {
                    case '📦 Olingan buyurtma': {
                            $master = Master::where('tg_id', $chatId)->first();
                            $order = Order::where(['master_id' => $master->id])
                                ->whereIn('status', ['take_order', 'do_work', 'finish'])
                                ->first();
                            if ($order) {
                                return  $this->masterActiveOrder($order, $chatId, messageData($order, $order->status));
                            } else {
                                return  $this->homepage($chatId, messageData($order, 'none'));
                            }
                        }
                    case '📝 Aktiv buyurtma': {
                            return $this->activeOrder($chatId);
                        }
                    case '✅ Bajarilgan buyurtmalar': {

                            return  $this->doneOrder($chatId);
                        }
                    case '💰 Balans': {

                            return $this->balance($chatId);
                        }
                    default:
                        return $this->homepage($chatId, "🏠 Asosiy menu");
                        break;
                }
            } else {
                $first_name = $update['message']['chat']['first_name'];
                $username = $update['message']['chat']['username'];
                Master::create([
                    'tg_id' => $chatId,
                    'full_name' => $first_name,
                    'username' => $username,
                    'status' => 'send_phone_q',
                ]);
                $this->requestPhoneNumber($chatId, messageData($username, 'send_phone_q'));
                return;
            }
            return response('OK', 200);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error('TelegramBotController webhook error: ' . $e->getMessage());
        }
    }

    // Function to send a message to the Telegram chat
    protected function sendMessage($chatId, $message, $sleep = 0)
    {
        $token = tg_token(); // Function to get your bot token
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $deleteMessage = "https://api.telegram.org/bot$token/deleteMessage";

        // Make an HTTP POST request to the Telegram API to send the message
        $response = Http::post($url, [
            'chat_id' => $chatId,
            'text' => $message,
        ]);

        // Check if the message was sent successfully and if $sleep is true
        if ($sleep > 0 && $response->successful()) {
            // if ($sleep && $response->successful()) {
            // Get the message ID from the response
            //   return  $response->json()['result']['message_id'];
            $messageId = $response->json()['result']['message_id'];

            // // Wait for 3 seconds
            sleep($sleep);

            // // Make an HTTP POST request to delete the message
            Http::post($deleteMessage, [
                'chat_id' => $chatId,
                'message_id' => $messageId
            ]);
        }

        return $response->json();
    }
    protected function deleteMessage($chatId, $messageId, $sleep = 0)
    {
        $token = tg_token(); // Function to get your bot token
        $url = "https://api.telegram.org/bot$token/deleteMessage";
        sleep($sleep);
        Http::post($url, [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ]);
    }

    protected function requestPhoneNumber($chatId, $message)
    {
        $token = tg_token();
        $url = "https://api.telegram.org/bot$token/sendMessage";

        $keyboard = [
            'keyboard' => [[
                ['text' => "📞 Raqam jo'natish", 'request_contact' => true],
            ]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];

        // Send a message with the custom keyboard to request phone number
        $response = Http::post($url, [
            'chat_id' => $chatId,
            'text' => $message,
            'reply_markup' => json_encode($keyboard),
        ]);

        return $response->json();
    }
    protected function homepage($chatId, $message)
    {
        $token = tg_token();
        $url = "https://api.telegram.org/bot$token/sendMessage";

        $keyboard = [
            'keyboard' => [
                [
                    ['text' => '📝 Aktiv buyurtma'],
                    ['text' => '📦 Olingan buyurtma'],

                ],
                [
                    ['text' => '✅ Bajarilgan buyurtmalar'],
                    ['text' => '💰 Balans'],
                ],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];
        // Send a message with the custom keyboard to request phone number
        Http::post($url, [
            'chat_id' => $chatId,
            'text' => $message,
            'reply_markup' => json_encode($keyboard),
        ]);

        // return $response->json();
    }
    protected function contract($chatId, $message = '')
    {
        $token = tg_token();
        $url = "https://api.telegram.org/bot$token/sendMessage";

        $keyboard = [
            'keyboard' => [[
                ['text' => '🆗'],
            ]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];

        // Send a message with the custom keyboard to request phone number
        $response = Http::post($url, [
            'chat_id' => $chatId,
            'text' => $message,
            'reply_markup' => json_encode($keyboard),
        ]);

        return $response->json();
    }


    public function takeOrder($data, $chatId)
    {
        $token = tg_token();
        $url = "https://api.telegram.org/bot$token/editMessageText";
        $url2 = "https://api.telegram.org/bot$token/sendMessage";
        $groupMessage = messageData($data, 'take_order_group');
        $masterMessage = messageData($data, 'take_order');
        $SendGroupOrder = SendGroupOrder::where('order_id', $data->id)->get();
        // Http::post($url2, [
        //     'chat_id' => $chatId,
        //     'text' => $masterMessage,
        // ]);
        $this->homepage($chatId, $masterMessage);
        foreach ($SendGroupOrder as $key => $item) {
            # code...
            Http::post($url, [
                'chat_id' => $item->chat_id,
                'text' => $groupMessage,
                'message_id' => $item->msg_id, // Message with emoji and markdown
                // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
                // 'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
            ]);
        }
    }


    public function masterActiveOrder($order, $chatId, $message)
    {
        $token = tg_token();
        $url = "https://api.telegram.org/bot$token/sendMessage";

        if ($order->status == 'take_order' || $order->status == 'finish') {
            return   $this->homepage($chatId, $message);
        } else {
            return  Http::post($url, [
                'chat_id' => $chatId,
                'text' => $message, // Message with emoji and markdown
                // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '🆗 YAKUNLASH', 'callback_data' => 'finish_' . $order->id], //
                        ],
                    ],
                ]), // Inline buttons
            ]);
        }
    }

    public function orderFinish($chatId, $order, $messageId)
    {

        $token = tg_token();
        $urlsend = "https://api.telegram.org/bot$token/sendMessage";
        $urledit = "https://api.telegram.org/bot$token/editMessageText";
        $finishText = messageData($order, 'finish');
        $finishGroupText = messageData($order, 'finish_group');
        $SendGroupOrder = SendGroupOrder::where('order_id', $order->id)->get();
        foreach ($SendGroupOrder as $key => $item) {
            # code...
            Http::post($urledit, [
                'chat_id' => $item->chat_id,
                'text' => $finishGroupText,
                'message_id' => $item->msg_id, // Message with emoji and markdown
                // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
                // 'reply_markup' => json_encode($inlineKeyboard), // Inline buttons
            ]);
        }
        Http::post($urledit, [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $finishText, // Message with emoji and markdown
            // 'reply_markup' => json_encode($inlineKeyboard2), // Inline buttons
        ]);
        return;
    }

    public function activeOrder($chatId)
    {
        $token = tg_token();
        $order = Order::where('status', 'ether')->get();
        if (count($order) == 0) {
            return   $this->homepage($chatId, messageData($order, 'none'));
        } else {
            $url = "https://api.telegram.org/bot$token/sendMessage";
            $messageText = '';
            $keybord = [];
            $targetId = 0;
            foreach ($order as $index => $item) {
                if ($index == 0) {
                    $targetId = $item->id;
                    $messageText = messageData($item, 'ether');
                    $keybord = [
                        ...$keybord,
                        ['text' => "🔘 " . $index + 1, 'callback_data' => 'activeorder_target_' . $item->id],
                    ];
                } else {
                    $keybord = [
                        ...$keybord,
                        ['text' => $index + 1, 'callback_data' => 'activeorder_target_' . $item->id],
                    ];
                }
            }
            return   Http::post($url, [
                'chat_id' => $chatId,
                'text' => $messageText,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [...$keybord],
                        [
                            [
                                'text' => '📥 BUYURTMANI OLISH',
                                // 'callback_data' => 'order_' . $data->id
                                'url' => "https://t.me/".botName()."?start=$targetId"
                            ],
                        ],
                    ],
                ]),
            ]);
        }
    }
    public function doneOrder($chatId)
    {
        $token = tg_token();
        $master = Master::where('tg_id', $chatId)->first();
        $order = Order::where(['master_id' => $master->id,])
            ->whereIn('status', ['pay_finally', 'finally'])
            ->get();
        // $this->homepage($chatId, "Mavjud emas 444");
        if (count($order) == 0) {
           return  $this->homepage($chatId, messageData(1, 'none'));
        } else {
            $url = "https://api.telegram.org/bot$token/sendMessage";
            $messageText = 'sss';
            $keybord = [];
            $targetId = 0;
            foreach ($order as $index => $item) {
                # code...
                if ($index == 0) {
                    // #bajarilmoqda
                    // #aktiv
                    $targetId = $item->id;
                    $messageText = messageData($item, $item->status);
                    $keybord = [
                        ...$keybord,
                        ['text' => "🔘 " . $index + 1, 'callback_data' => 'doneactiveorder_target_' . $item->id],
                    ];
                } else {
                    $keybord = [
                        ...$keybord,
                        ['text' => $index + 1, 'callback_data' => 'doneactiveorder_target_' . $item->id],
                    ];
                }
            }

            return   Http::post($url, [
                'chat_id' => $chatId,
                'text' => $messageText, // Message with emoji and markdown
                // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [...$keybord],

                    ],
                ]),
            ]);
        }
    }
    public function doneTargetOrder($chatId, $targetid)
    {
        $token = tg_token();
        $master = Master::where('tg_id', $chatId)->first();
        $order = Order::where(['master_id' => $master->id,])
            ->whereIn('status', ['pay_finally', 'finally'])
            ->get();
        // $this->homepage($chatId, "Mavjud emas 444");
        if (count($order) == 0) {
            return  $this->homepage($chatId, messageData(1, 'none'));
        } else {
            $url = "https://api.telegram.org/bot$token/sendMessage";
            $messageText = 'sss';
            $keybord = [];
            foreach ($order as $index => $item) {
                # code...

                // #bajarilmoqda
                // #aktiv
                $targetId = $item->id;
                if ($item->id == $targetid) {
                    $messageText = messageData($item, $item->status);
                    $keybord = [
                        ...$keybord,
                        ['text' => "🔘 " . $index + 1, 'callback_data' => 'doneactiveorder_target_' . $targetid],
                    ];
                } else {
                    $keybord = [
                        ...$keybord,
                        ['text' => $index + 1, 'callback_data' => 'doneactiveorder_target_' . $item->id],
                    ];
                }
            }

            return  Http::post($url, [
                'chat_id' => $chatId,
                'text' => $messageText, // Message with emoji and markdown
                // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [...$keybord],

                    ],
                ]),
            ]);
        }
    }
    public function targetOrder($chatId, $targetid)
    {
        $token = tg_token();
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $target_order = Order::find($targetid);
        if ($target_order) {
            if ($target_order->status == 'ether') {
                $keybord = [];
                $order = Order::where('status', 'ether')->get();
                $messageText = '';
                foreach ($order as $index => $item) {
                    # code...
                    if ($item->id == $targetid) {
                        $messageText = messageData($item, 'ether');
                        $keybord = [
                            ...$keybord,
                            ['text' => "🔘 " . $index + 1, 'callback_data' => 'activeorder_target_' . $item->id],

                        ];
                    } else {
                        $keybord = [
                            ...$keybord,
                            ['text' => $index + 1, 'callback_data' => 'activeorder_target_' . $item->id],
                        ];
                    }
                }

                return  Http::post($url, [
                    'chat_id' => $chatId,
                    'text' => $messageText, // Message with emoji and markdown
                    // 'parse_mode' => 'Markdown', // Enable markdown for text formatting
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [...$keybord],
                            [
                                [
                                    'text' => '📥 BUYURTMANI OLISH',
                                    // 'callback_data' => 'order_' . $data->id
                                    'url' => "https://t.me/".botName()."?start=$targetid"
                                ],
                            ],
                        ],
                    ]),
                ]);
            } else {
                return  $this->homepage($chatId, messageData($target_order, 'order_received'));
            }
        } else {
            return  $this->homepage($chatId, messageData(1, 'none'));
        }
    }
    public function balance($chatId)
    {
        $token = tg_token();
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $master = Master::where('tg_id', $chatId)->first();
        $this->homepage($chatId, messageData($master, 'balans'));
    }
}
