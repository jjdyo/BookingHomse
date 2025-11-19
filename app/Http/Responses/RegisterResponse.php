<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        // After registration, users must verify their email.
        $user = $request->user();
        if ($user && ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail) && ! $user->hasVerifiedEmail()) {
            // Auto-send verification email if not recently sent (60s cooldown)
            $lastSentAt = $request->session()->get('verification.last_sent_at');
            $shouldSend = ! $lastSentAt || now()->diffInSeconds($lastSentAt) >= 60;

            if ($shouldSend) {
                $user->sendEmailVerificationNotification();
                $request->session()->put('verification.last_sent_at', now());

                return Redirect::route('verification.notice')->with('status', 'verification-link-sent');
            }
        }

        return Redirect::route('verification.notice');
    }
}
