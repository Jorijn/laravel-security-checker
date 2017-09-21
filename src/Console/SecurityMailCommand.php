<?php

namespace Jorijn\LaravelSecurityChecker\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Jorijn\LaravelSecurityChecker\Mailables\SecurityMail;
use SensioLabs\Security\SecurityChecker;

class SecurityMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'security-check:email';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Emails any vulnerabilities for packages you have in your composer.lock file.';

    /**
     * @var SecurityChecker
     */
    protected $checker;

    /**
     * SecurityCommand constructor.
     *
     * @param SecurityChecker $checker
     */
    public function __construct(SecurityChecker $checker)
    {
        parent::__construct();

        $this->checker = $checker;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get the path to composer.lock
        $composerLock = base_path('composer.lock');

        // and feed it into the SecurityChecker
        $checkResult = $this->checker->check($composerLock);

        // if the user didn't want any email if there are no results,
        // cancel execution here.
        $proceed = config('laravel-security-checker.email_even_without_vulnerabilities', false);
        if (count($checkResult) === 0 && $proceed !== true) {
            return 0;
        }

        // get the recipients and filter out any configuration mistakes
        $recipients = collect(config('laravel-security-checker.recipients', [ ]))->filter(function ($recipient) {
            return !is_null($recipient) && !empty($recipient);
        });

        if ($recipients->count() === 0) {
            $this->error(__('laravel-security-checker::messages.no_recipients_configured'));
            return 1;
        }

        Mail::to($recipients->toArray())->send(new SecurityMail($checkResult));

        return 0;
    }
}
