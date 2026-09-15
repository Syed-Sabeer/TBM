@php $company = auth()->user()->company; @endphp

{{--
    Shown whenever the account cannot trade. It names the reason and the next
    step, because "pricing is hidden" without a cause is the fastest way to
    lose a new wholesale customer.
--}}
<div class="notice notice-warn acct-status">
    <x-icon name="lock"/>
    <div>
        @if (! $company->status->canTrade())
            <b>Account {{ strtolower($company->status->label()) }}</b>
            <p>
                @if ($company->status === \App\Enums\AccountStatus::PendingApproval)
                    Your application is with an account manager. Pricing and ordering open as soon as it is approved — usually within one business day. You can browse the catalogue and stock in the meantime.
                @else
                    Ordering is paused on this account. {{ $company->accountManager?->name ?? 'Your account manager' }} can pick this up — {{ config('tbm.company.accounts_email') }}.
                @endif
            </p>
        @else
            <b>Resale certificate needed</b>
            <p>
                We need a current resale certificate on file before this account can order again.
                Send it to {{ config('tbm.company.compliance_email') }} and we will clear it the same day.
            </p>
        @endif
    </div>
</div>
