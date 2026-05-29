<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Quick deliverability check: php artisan mail:test you@example.com
 */
class TestMail extends Command
{
    protected $signature = 'mail:test {to}';

    protected $description = 'Send a test email to verify the mail configuration.';

    public function handle(): int
    {
        $to = $this->argument('to');
        $this->info('Mailer: ' . config('mail.default') . ' via ' . config('mail.mailers.smtp.host'));

        try {
            Mail::raw('Boleto test email — your mail integration works! Sent ' . now()->toDateTimeString(), function ($m) use ($to) {
                $m->to($to)->subject('Boleto mail test');
            });
            $this->info("Sent test email to {$to}. Check the inbox (and spam).");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('FAILED: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
