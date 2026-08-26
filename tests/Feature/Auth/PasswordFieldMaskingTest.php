<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

/**
 * The show/hide toggle binds the input type through Alpine. An input with no
 * static type falls back to "text", so a page where Alpine fails to load would
 * render every password field in clear text.
 *
 * @param  array<int, string>  $ids
 */
function assertPasswordFieldsAreMasked(string $html, array $ids): void
{
    foreach ($ids as $id) {
        $tag = str($html)->after('id="'.$id.'"')->before('/>')->toString();

        expect($tag)->toContain('type="password"');
    }
}

test('the login password field is masked without javascript', function () {
    $html = $this->get(route('login'))->assertOk()->getContent();

    assertPasswordFieldsAreMasked($html, ['password']);
});

test('the password confirmation field is masked without javascript', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get(route('password.confirm'))->assertOk()->getContent();

    assertPasswordFieldsAreMasked($html, ['password']);
});

test('the reset password fields are masked without javascript', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (object $notification) {
        $html = $this->get(route('password.reset', $notification->token))->assertOk()->getContent();

        assertPasswordFieldsAreMasked($html, ['password', 'password_confirmation']);

        return true;
    });
});
