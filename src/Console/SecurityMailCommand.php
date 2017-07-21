<?php

namespace Jorijn\LaravelSecurityChecker\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Jorijn\LaravelSecurityChecker\Mailables\SecurityMail;
use SensioLabs\Security\SecurityChecker;

class SecurityMailCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'security-check:email';

    /**
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
     *
     */
    public function fire()
    {
        // get the path to composer.lock
        $composerLock = base_path('composer.lock');

        // and feed it into the SecurityChecker
        $checkResult = $this->checker->check($composerLock);

        // get the recipients and filter out any configuration mistakes
        $recipients = collect(config('laravel-security-checker.recipients', []))->filter(function ($recipient) {
            return !is_null($recipient) && !empty($recipient);
        });

        if (is_null($recipients) || count($recipients) === 0) {
            $this->error(trans('laravel-security-checker::messages.no_recipients_configured'));
            return 1;
        }

        Mail::to($recipients->toArray())->send(new SecurityMail($checkResult));
    }
}