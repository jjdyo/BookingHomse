<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
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
            return Redirect::route('verification.notice');
        }

        return Redirect::route('dashboard');
    }
}
