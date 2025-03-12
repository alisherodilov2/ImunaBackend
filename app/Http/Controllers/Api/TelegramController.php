<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    protected $telegramBotToken;
    protected $channelId;


    // Webhook handler
    public function webhookHandler(Request $request)
    {
        // Telegram update'ini qabul qilish
        $webhookData = $request->all();
        // Xabarni log fayliga yozish
        Log::info('Telegram Webhook Message: ', $webhookData);
        // if (isset($update['my_chat_member'])) {
        //     $status = $update['my_chat_member']['new_chat_member']['status'];
        //     $chatId = $update['my_chat_member']['chat']['id'];
        //     $chatTitle = $update['my_chat_member']['chat']['title'] ?? 'No title';

        //     Log::info("Bot added to the channel '{$chatTitle}' with ID: {$chatId}");
        //     // Agar bot kanalga admin bo'lib qo'shilgan bo'lsa, logga yozamiz
        //     if ($status === 'administrator') {
        //         // Log faylga yozish
        //     }
        // }
        // // Xabar borligini tekshirish
        // if (isset($update['message']['text'])) {
        //     $text = $update['message']['text'];
        //     $chatId = $update['message']['chat']['id'];
        //     if ($text == '/start') {
        //        return $this->sendMessage($chatId, 'id' . $chatId);
        //     }
        //     // Kodni matndan olish
        //     $code = true;
        //     // $code = $this->extractCode($text);

        //     if ($code) {
        //         // Kanal xabarlaridan kodni qidirish
        //         $movie = $this->findMovieByCode($code);

        //         // Agar film topilsa, foydalanuvchiga javob yuborish
        //         if ($movie) {
        //             $this->sendMessage($chatId, 'Film topildi: ' . $movie);
        //         } else {
        //             $this->sendMessage($chatId, 'Bunday kodli film topilmadi.');
        //         }
        //     } else {
        //         $this->sendMessage($chatId, 'Matnda hech qanday kod topilmadi.');
        //     }
        // }
        // $businessMessage = $webhookData['business_message'];
        // // Kerakli ma'lumotlarni olish
        // $text = $businessMessage['text'];
        // $businessConnectionId = $businessMessage['business_connection_id'];
        // $fromId = $businessMessage['from']['id'];
        // return $this->sendMessage($businessConnectionId, $fromId, $text);
       
    }

    // Kodni matndan ajratib olish
    private function extractCode($text)
    {
        preg_match('/\b[A-Z0-9]{5,}\b/', $text, $matches);
        return $matches[0] ?? null;
    }

    // Kod orqali kanal xabarlaridan qidirish
    private function findMovieByCode($code)
    {
        $token  = '5700223914:AAE9TvqAgjPOhFgZNF2YV_-BrxeJ5pvyuTY';
        // @rasuljon1234_bot (https://t.me/rasuljon1234_bot)
        // $token  = '5601860449:AAGBFoFw2Y8rWu4rTCBFfZufk7wAm1e0jrQ';
        // Kanal xabarlarini olish uchun Telegram API dan foydalanamiz
        $url = "https://api.telegram.org/bot$token/getUpdates";

        // HTTP so'rov orqali kanal xabarlarini olish
        $response = Http::post($url, [
            'chat_id' => '-1002219667930', // Kanal ID sini ishlatamiz
            'limit' => 100 // Oxirgi 100 xabarni olamiz (ko'proq qidirishingiz ham mumkin)
        ]);

        $updates = $response->json();

        // // Xabarlarni qidirish
        // if (!empty($updates['result'])) {
        //     foreach ($updates['result'] as $message) {
        //         if (isset($message['channel_post']['text']) && strpos($message['channel_post']['text'], $code) !== false) {
        //             return $message['channel_post']['text'];
        //         }
        //     }
        // }

        return json_encode($updates); // Agar film topilmasa
    }

    // Telegramga xabar yuborish
    private function sendMessage($business_connection_id, $chatId, $message)
    {
        $token  = '5700223914:AAE9TvqAgjPOhFgZNF2YV_-BrxeJ5pvyuTY';
        // Telegram API orqali xabar yuborish uchun HTTP so'rov
        $url = "https://api.telegram.org/bot$token/sendMessage";

        Http::post($url, [
            'business_connection_id' => $business_connection_id,
            'chat_id' => $chatId,
            'text' => $message,
        ]);
    }
}
