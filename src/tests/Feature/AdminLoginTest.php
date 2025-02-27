<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    // role が admin のユーザーを作成
    private function createStaffUser()
    {
        return User::factory()->create([
            'role' => 'admin',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    //メールアドレスの入力必須テスト
    public function test_email_is_required()
    {
        $this->createStaffUser();

        $response = $this->post('/login', [
            'password' => 'password',
            'email' => ''
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    //パスワードの入力必須テスト
    public function test_password_is_required()
    {
        $this->createStaffUser();

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    //ログイン情報が一致しない場合
    public function test_mismatch_of_login()
    {
        $this->createStaffUser();

        $response = $this->post('/login', [
            'email' => 'no-reply@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['login_error' => 'ログイン情報が登録されていません。']);
    }
}