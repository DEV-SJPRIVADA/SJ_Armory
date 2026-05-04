<?php

namespace Tests\Feature;

use App\Mail\UserAccessCredentialsMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserSendAccessCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_access_credentials_to_active_user(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'ADMIN']);
        $target = User::factory()->create([
            'role' => 'RESPONSABLE',
            'is_active' => true,
            'must_change_password' => false,
            'password' => 'old-password-stable',
        ]);

        $response = $this->actingAs($admin)->post(route('users.send-access-credentials', $target));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status');

        Mail::assertSent(UserAccessCredentialsMail::class, function (UserAccessCredentialsMail $mail) use ($target) {
            return $mail->hasTo($target->email);
        });

        $target->refresh();
        $this->assertTrue($target->must_change_password);
        $this->assertFalse(Hash::check('old-password-stable', $target->password));
    }

    public function test_inactive_user_cannot_receive_credentials(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'ADMIN']);
        $target = User::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('users.send-access-credentials', $target));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_non_admin_cannot_send_credentials(): void
    {
        Mail::fake();

        $responsible = User::factory()->create(['role' => 'RESPONSABLE']);
        $target = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($responsible)->post(route('users.send-access-credentials', $target));

        $response->assertForbidden();

        Mail::assertNothingSent();
    }
}
