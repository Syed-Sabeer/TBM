<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses.index', [
            'addresses' => $request->user()->company->addresses()->defaultFirst()->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Address::class);

        return view('account.addresses.form', ['address' => new Address()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Address::class);

        $address = $request->user()->company->addresses()->create($this->validated($request));

        $this->settleDefault($address, $request->boolean('is_default'));

        return redirect()->route('account.addresses.index')->with('status', 'Address added.');
    }

    public function edit(Address $address): View
    {
        $this->authorize('update', $address);

        return view('account.addresses.form', compact('address'));
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        /*
         | The address record is edited in place, and no historical document
         | changes with it — orders keep their own frozen copy of where they
         | shipped.
         */
        $address->update($this->validated($request));

        $this->settleDefault($address, $request->boolean('is_default'));

        return redirect()->route('account.addresses.index')->with('status', 'Address updated.');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorize('delete', $address);

        $address->delete();

        return back()->with('status', 'Address removed. Orders that shipped there are unaffected.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'street' => ['required', 'string', 'max:160'],
            'street_2' => ['nullable', 'string', 'max:160'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:32'],
            'postcode' => ['required', 'string', 'max:16'],
            'country' => ['nullable', 'string', 'max:64'],
            'is_default' => ['nullable', 'boolean'],
            'is_residential' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** Exactly one default per account. */
    private function settleDefault(Address $address, bool $isDefault): void
    {
        if (! $isDefault) {
            return;
        }

        DB::transaction(function () use ($address) {
            Address::where('company_id', $address->company_id)
                ->whereKeyNot($address->id)
                ->update(['is_default' => false]);

            $address->update(['is_default' => true]);
        });
    }
}
