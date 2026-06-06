<?php

namespace App\Http\Controllers\Admin;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class OfferController extends AdminController
{
    protected string $modelClass = \App\Models\Offer::class;
    protected string $resource = 'offer';

    protected function columns(): array
    {
        return ['id', 'name', 'note', 'position'];
    }

    protected function fields(?Model $item = null): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['name' => 'note', 'label' => 'Note / description', 'type' => 'textarea'],
            ['name' => 'color', 'label' => 'Badge colour (hex, e.g. #ff9900)', 'type' => 'text'],
            ['name' => 'position', 'label' => 'Position (order)', 'type' => 'number'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ];
    }

    private function payload(Request $request): array
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'note'     => 'nullable|string|max:500',
            'color'    => 'nullable|string|max:20',
            'position' => 'nullable|integer|min:0',
        ]);
        $data['color'] = $data['color'] ?: '#0fb39a';
        $data['position'] = (int) ($data['position'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }

    public function index()
    {
        $items = Offer::orderBy('position')->orderBy('id')->paginate(30);
        return view('admin.crud.index', [
            'items' => $items,
            'filters' => ['q' => '', 'searchable' => false, 'fk' => [], 'active' => false],
            'resource' => $this->resource,
            'columns' => $cols = $this->columns(),
            'fkLabels' => [],
            'title' => 'Offers',
        ]);
    }

    public function create()
    {
        return view('admin.crud.form', [
            'item' => new Offer(['is_active' => true, 'position' => 0, 'color' => '#0fb39a']),
            'resource' => $this->resource,
            'fields' => $this->fields(),
            'title' => 'Create Offer',
        ]);
    }

    public function store(Request $request)
    {
        Offer::create($this->payload($request));
        return redirect()->route('admin.offers.index')->with('status', 'Offer created.');
    }

    public function show($id)
    {
        $item = Offer::findOrFail($id);
        return view('admin.crud.show', ['item' => $item, 'resource' => $this->resource, 'fields' => $this->fields($item)]);
    }

    public function edit($id)
    {
        $item = Offer::findOrFail($id);
        return view('admin.crud.form', [
            'item' => $item,
            'resource' => $this->resource,
            'fields' => $this->fields($item),
            'title' => 'Edit Offer',
        ]);
    }

    public function update(Request $request, $id)
    {
        Offer::findOrFail($id)->update($this->payload($request));
        return redirect()->route('admin.offers.index')->with('status', 'Offer updated.');
    }

    public function destroy($id)
    {
        Offer::findOrFail($id)->delete();
        return back()->with('status', 'Offer deleted.');
    }
}
