<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailResponse implements VerifyEmailResponseContract
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
            return new JsonResponse('', 204);
        }

        $message = $request->user() && $request->user()->hasVerifiedEmail()
            ? 'Your email has been successfully verified.'
            : 'We were unable to verify your email. Please try again or request a new link.';

        return redirect()->route('home')->with(
            $request->user() && $request->user()->hasVerifiedEmail() ? 'success' : 'error',
            $message
        );
    }
}
