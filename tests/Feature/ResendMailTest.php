<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdministratorInvitation;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Resend\Client as ResendClient;
use Resend\Transporters\HttpTransporter;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Tests\TestCase;

class ResendMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'resend',
            'mail.from.address' => 'app@voluntrack.js7y.io',
            'mail.from.name' => 'Voluntrack',
            'services.resend.key' => 're_test_not_a_real_key',
        ]);
        URL::forceRootUrl('https://voluntrack.js7y.io');
        URL::forceScheme('https');
    }

    public function test_configured_mailer_resolves_the_native_resend_transport(): void
    {
        $this->assertSame('resend', config('mail.mailers.resend.transport'));
        $this->assertInstanceOf(ResendTransport::class, Mail::mailer()->getSymfonyTransport());
    }

    public function test_invitation_and_password_reset_are_sent_with_the_verified_sender_and_public_links(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"invitation-test-id"}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"password-reset-test-id"}'),
        ]));
        $stack->push(Middleware::history($history));
        $this->useMockedApi($stack);

        $user = new User(['name' => 'QC Administrator', 'email' => 'recipient@example.test']);
        $user->notify(new AdministratorInvitation('invitation-token', 'QC NEMBA'));
        $user->notify(new ResetPassword('reset-token'));

        $this->assertCount(2, $history);
        foreach ($history as $index => $transaction) {
            $request = $transaction['request'];
            $payload = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('https://api.resend.com/emails', (string) $request->getUri());
            $this->assertSame('POST', $request->getMethod());
            $this->assertSame('Bearer re_test_not_a_real_key', $request->getHeaderLine('Authorization'));
            $sender = Address::create($payload['from']);
            $this->assertSame('app@voluntrack.js7y.io', $sender->getAddress());
            $this->assertSame('Voluntrack', $sender->getName());
            $this->assertSame(['recipient@example.test'], $payload['to']);
            $token = $index === 0 ? 'invitation-token' : 'reset-token';
            $this->assertStringContainsString('https://voluntrack.js7y.io/account/password/reset/'.$token, $payload['html']);
            $this->assertNotEmpty($payload['text']);
        }
        $invitation = json_decode((string) $history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Manage QC NEMBA on Voluntrack', $invitation['subject']);
    }

    public function test_resend_rejection_is_an_explicit_failure_not_a_successful_log_fallback(): void
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(401, ['Content-Type' => 'application/json'], '{"statusCode":401,"name":"validation_error","message":"API key is invalid"}'),
        ]));
        $this->useMockedApi($stack);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('API key is invalid');
        (new User(['name' => 'Volunteer', 'email' => 'recipient@example.test']))
            ->notify(new ResetPassword('reset-token'));
    }

    private function useMockedApi(HandlerStack $stack): void
    {
        $transporter = new HttpTransporter(
            new Client(['handler' => $stack]),
            BaseUri::from('api.resend.com'),
            Headers::withAuthorization(ApiKey::from('re_test_not_a_real_key')),
        );
        Mail::mailer()->setSymfonyTransport(new ResendTransport(new ResendClient($transporter)));
    }
}
