<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_new_users_can_register(): void
    {
        $division = \App\Models\Division::create([
            'name' => 'IT',
        ]);

        $response = $this->post('/register', [
            'userid' => 'USR001',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $division->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
