<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AddonLandingController extends Controller
{
    public function outreach(Request $request): View
    {
        $this->authorizeHost($request);

        return view('public.addons.outreach');
    }

    public function unibox(Request $request): View
    {
        $this->authorizeHost($request);

        return view('public.addons.unibox');
    }

    public function workspace(Request $request): View
    {
        $this->authorizeHost($request);

        return view('public.addons.workspace');
    }

    public function sms(Request $request): View
    {
        $this->authorizeHost($request);

        return view('public.addons.sms');
    }

    public function whatsapp(Request $request): View
    {
        $this->authorizeHost($request);

        return view('public.addons.whatsapp');
    }

    public function aiAssistant(Request $request): View
    {
        $this->authorizeHost($request);

        return view('public.addons.ai-assistant');
    }

    private function authorizeHost(Request $request): void
    {
        abort_unless(in_array(strtolower($request->getHost()), ['getmailpurse.com', 'www.getmailpurse.com', 'localhost', '127.0.0.1', '::1'], true), 404);
    }
}
