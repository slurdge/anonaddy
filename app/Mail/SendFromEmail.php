<?php

namespace App\Mail;

use App\Alias;
use App\EmailData;
use App\Helpers\AlreadyEncryptedSigner;
use App\Traits\CheckUserRules;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Swift_Signers_DKIMSigner;

class SendFromEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, CheckUserRules;

    protected $email;
    protected $user;
    protected $alias;
    protected $sender;
    protected $emailSubject;
    protected $emailText;
    protected $emailHtml;
    protected $emailAttachments;
    protected $dkimSigner;
    protected $encryptedParts;
    protected $displayFrom;
    protected $fromEmail;
    protected $size;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, Alias $alias, EmailData $emailData)
    {
        $this->user = $user;
        $this->alias = $alias;
        $this->sender = $emailData->sender;
        $this->emailSubject = $emailData->subject;
        $this->emailText = $emailData->text;
        $this->emailHtml = $emailData->html;
        $this->emailAttachments = $emailData->attachments;
        $this->encryptedParts = $emailData->encryptedParts ?? null;
        $this->displayFrom = $user->from_name ?? null;
        $this->size = $emailData->size;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->alias->isCustomDomain()) {
            if ($this->alias->aliasable->isVerifiedForSending()) {
                $this->fromEmail = $this->alias->email;
                $returnPath = $this->alias->email;

                $this->dkimSigner = new Swift_Signers_DKIMSigner(config('anonaddy.dkim_signing_key'), $this->alias->domain, config('anonaddy.dkim_selector'));
                $this->dkimSigner->ignoreHeader('Return-Path');
            } else {
                $this->fromEmail = config('mail.from.address');
                $returnPath = config('anonaddy.return_path');
            }
        } else {
            $this->fromEmail = $this->alias->email;
            $returnPath = 'mailer@'.$this->alias->parentDomain();
        }

        $this->email =  $this
            ->from($this->fromEmail, $this->displayFrom)
            ->subject(base64_decode($this->emailSubject))
            ->text('emails.reply.text')->with([
                'text' => base64_decode($this->emailText)
            ])
            ->withSwiftMessage(function ($message) use ($returnPath) {
                $message->getHeaders()
                        ->addTextHeader('Return-Path', config('anonaddy.return_path'));

                $message->setId(bin2hex(random_bytes(16)).'@'.$this->alias->domain);

                if ($this->encryptedParts) {
                    $alreadyEncryptedSigner = new AlreadyEncryptedSigner($this->encryptedParts);

                    $message->attachSigner($alreadyEncryptedSigner);
                }

                if ($this->dkimSigner) {
                    $message->attachSigner($this->dkimSigner);
                }
            });

        if ($this->emailHtml) {
            $this->email->view('emails.reply.html')->with([
                'html' => base64_decode($this->emailHtml)
            ]);
        }

        foreach ($this->emailAttachments as $attachment) {
            $this->email->attachData(
                base64_decode($attachment['stream']),
                base64_decode($attachment['file_name']),
                ['mime' => base64_decode($attachment['mime'])]
            );
        }

        $this->checkRules();

        $this->email->with([
            'shouldBlock' => $this->size === 0
        ]);

        if ($this->alias->isCustomDomain() && !$this->dkimSigner) {
            $this->email->replyTo($this->alias->email, $this->displayFrom);
        }

        if ($this->size > 0) {
            $this->alias->increment('emails_sent');

            $this->user->bandwidth += $this->size;
            $this->user->save();
        }

        return $this->email;
    }
}
