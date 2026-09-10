<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerUnsubscribeController extends Controller
{
    /**
     * Opt an email out of bulk customer notifications. Reached only via a
     * signed URL (see CustomerBroadcastMail) — no login required, since a
     * guest customer with no account must still be able to unsubscribe.
     */
    public function __invoke(Request $request): View
    {
        $email = mb_strtolower(trim((string) $request->query('email')));

        abort_if($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL), 404);

        CustomerProfile::withTrashed()->updateOrCreate(
            ['email' => $email],
            ['marketing_opt_out' => true, 'marketing_opt_out_at' => now()],
        );

        return view('frontend.unsubscribed', ['email' => $email]);
    }
}
