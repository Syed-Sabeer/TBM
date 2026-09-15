<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "We should have the option to choose / create new warehouses and put
 * inventory there." A site created here appears immediately as a stock column,
 * a ship-from option at checkout, and a target for the morning import.
 */
class WarehouseController extends Controller
{
    public function index(): View
    {
        return view('admin.inventory.warehouses', [
            'warehouses' => Warehouse::withCount('inventoryLevels')->orderBy('position')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.inventory.warehouse-form', [
            'warehouse' => new Warehouse(['is_active' => true, 'include_in_storefront' => true, 'type' => 'owned']),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $warehouse = Warehouse::create($this->validated($request));

        ActivityLog::record('Warehouse added', $warehouse->code.' — '.$warehouse->name, $warehouse);

        return redirect()->route('admin.warehouses.index')
            ->with('status', sprintf('%s is live. It now appears as a stock column and a ship-from option.', $warehouse->code));
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('admin.inventory.warehouse-form', [
            'warehouse' => $warehouse,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($this->validated($request, $warehouse));

        ActivityLog::record('Warehouse updated', $warehouse->code, $warehouse);

        return back()->with('status', $warehouse->code.' saved.');
    }

    /**
     * Deactivated rather than deleted while it still holds stock or appears on
     * an order — deleting would orphan history for no gain.
     */
    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->unitsOnHand() > 0 || $warehouse->orders()->exists()) {
            $warehouse->update(['is_active' => false, 'include_in_storefront' => false]);

            return back()->with('status', sprintf(
                '%s still holds stock or appears on orders, so it has been deactivated rather than deleted.',
                $warehouse->code
            ));
        }

        $warehouse->delete();

        return back()->with('status', 'Warehouse removed.');
    }

    private function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:8', Rule::unique('warehouses', 'code')->ignore($warehouse?->id)],
            'name' => ['required', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:80'],
            'type' => ['required', Rule::in(['owned', '3pl', 'consignment'])],
            'company_id' => ['nullable', 'exists:companies,id'],
            'street' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:32'],
            'postcode' => ['nullable', 'string', 'max:16'],
            'lead_time' => ['nullable', 'string', 'max:80'],
            'floor_space' => ['nullable', 'string', 'max:80'],
            'has_decoration' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'include_in_storefront' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['has_decoration'] = $request->boolean('has_decoration');
        $data['is_active'] = $request->boolean('is_active');

        // A consignment site holds one account's goods, so it must never be
        // offered as a ship-from to anybody else.
        $data['include_in_storefront'] = $data['type'] === 'consignment'
            ? false
            : $request->boolean('include_in_storefront');

        if ($data['type'] !== 'consignment') {
            $data['company_id'] = null;
        }

        return $data;
    }
}
