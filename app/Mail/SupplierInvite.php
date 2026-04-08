<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Core\User as CoreUser;

class SupplierInvite extends Mailable
{
    use Queueable, SerializesModels;

    public CoreUser $user;
    public string $plainPassword;
    public ?string $storeName;
    public ?string $systemName;

    /**
     * Create a new message instance.
     */
    public function __construct(CoreUser $user, string $plainPassword, ?string $storeName = null, ?string $systemName = null)
    {
        $this->user = $user;
        $this->plainPassword = $plainPassword;
        $this->storeName = $storeName;
        $this->systemName = $systemName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), 'Furnisync Shop')
            ->subject('Your supplier account at ' . ($this->systemName ?? config('app.name')))
            ->view('emails.supplier_invite')
            ->with([
                'name' => $this->user->full_name,
                'user_id' => $this->user->user_id,
                'password' => $this->plainPassword,
                'login_url' => 'https://furnisync.shop/login',
                'store_name' => $this->storeName,
                'system_name' => $this->systemName,
            ]);
    }
}
