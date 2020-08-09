<?php
#namespace Tests\AuthTest;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class AuthTest extends TestCase
{
    public function testRegisterUser()
    {
        $data = [
            "mobile_number" => 9957246635,
            "password" => "password"
        ];

        $this->json('POST', '/api/register', $data)
                ->seeStatusCode(200)
                ->seeJsonStructure([
                    'mobile_number',
                    'otp',
                    'created_at',
                    'updated_at',
                    'id'
            ]);
    }

    public function testVerifyOTP()

    {
        $data = [
            "mobile_number" => 8698522394,
            "otp" => "2544"
        ];

        $response = $this->json('POST', '/api/verifyotp', $data);
        $response->assertJson('success');
        $response->assertJson('OTP verified');
        $response->seeJson(['data' => $data]);
    }
   
}