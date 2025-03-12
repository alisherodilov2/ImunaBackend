<?php

namespace Database\Seeders;

use App\Models\Client as ModelsClient;
use App\Models\ClientResult;
use App\Models\ClientValue;
use App\Models\Services;
use Database\Factories\ClientFactory;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public $count;
    public $pay;

    public function __construct($count = 0, $pay = 0)
    {
        $this->count = $count;
        $this->pay = $pay;
    }
    public function run()
    {
         $count = $this->count;
         $pay = $this->pay; // Default qiymat 'user'
         for ($i = 0; $i < $count; $i++) {
             $parentClient = ModelsClient::factory()->create();
             $personId = (ModelsClient::where('user_id', $parentClient->user_id)->max('person_id') ?? 0) +1;
             $parentClient->update(['person_id' => $personId]); 
             $randomClients = Services::where('department_id',3)->inRandomOrder()->take(5)->get();
             $result = ModelsClient::create([
                'person_id' => $personId,
                 'parent_id' => $parentClient->id,
                 'first_name' => $parentClient->first_name,
                 'last_name' => $parentClient->last_name,
                 'sex' => $parentClient->sex,
                 'address' => $parentClient->address, // Tasodifiy manzil
                 'phone' => $parentClient->phone, // Tasodifiy manzil
                 'data_birth' => $parentClient->data_birth,
                //  'pay_total_price' => 0,
                 'total_price' => $randomClients->sum('price'),
                 'pay_total_price' => $pay ?  $randomClients->sum('price') : 0,
                 // 'advertisements_id'=>
                 // referring_doctor_id=>
                 'is_pay' =>  $pay ? 1 : 0,
                 'service_count' => $randomClients->count(),
                 'user_id' => $parentClient->user_id
             ]);
     
             foreach ($randomClients as $client) {
                 ClientValue::create([
                     'client_id' => $result->id,
                     'service_id' => $client->id,
                     'department_id' => $client->department_id,
                     'is_active' => 1,
                     'price' => $client->price,
                     'qty' => 1,
                     'is_pay' =>  $pay ? 1 : 0,
                     'pay_price' => $pay ? $client->price : 0,
                     'total_price' => $client->price,
                     'user_id' => $parentClient->user_id
                     // 'user_id' => $client->user_id
                 ]);

                 
                 ClientResult::create([
                     'client_id' => $result->id,
                     'department_id' => $client->department_id,
                 ]);


             }
         }
    }
}
