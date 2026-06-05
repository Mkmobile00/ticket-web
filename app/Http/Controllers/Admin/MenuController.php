<?php

namespace App\Http\Controllers\Admin;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuController extends AdminController
{
    protected string $modelClass = \App\Models\MenuItem::class;
    protected string $resource = 'menu';

    protected function columns(): array
    {
        return ['id', 'location', 'label', 'url', 'position'];
    }

    /** Fully custom form: location + parent are selects; the path is a dropdown (never typed). */
    protected function fields(?Model $item = null): array
    {
        // Possible parents: top-level items in the same location (exclude self).
        $parents = MenuItem::whereNull('parent_id')
            ->when($item && $item->exists, fn ($q) => $q->where('id', '!=', $item->id))
            ->orderBy('location')->orderBy('position')
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => ucfirst($m->location) . ': ' . $m->label])
            ->all();

        return [
            ['name' => 'location', 'label' => 'Location', 'type' => 'select', 'options' => ['header' => 'Header menu', 'footer' => 'Footer menu']],
            ['name' => 'parent_id', 'label' => 'Parent (for a submenu — leave empty for a top-level item)', 'type' => 'select', 'options' => $parents, 'placeholder' => '— Top level —'],
            ['name' => 'label', 'label' => 'Label (menu text)', 'type' => 'text'],
            ['name' => 'url', 'label' => 'Links to', 'type' => 'select', 'options' => MenuItem::LINK_OPTIONS],
            ['name' => 'position', 'label' => 'Position (order)', 'type' => 'number'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?Model $item = null): array
    {
        return [
            'location'  => 'required|in:header,footer',
            'parent_id' => 'nullable|integer|exists:menu_items,id',
            'label'     => 'required|string|max:80',
            'url'       => 'required|string|max:191',
            'position'  => 'nullable|integer|min:0',
        ];
    }

    private function payload(Request $request): array
    {
        $data = $request->validate($this->rules());
        $data['parent_id'] = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;
        $data['position'] = (int) ($data['position'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }

    public function index()
    {
        $items = MenuItem::orderBy('location')->orderBy('position')->paginate(50);
        return view('admin.crud.index', [
            'items' => $items,
            'filters' => ['q' => '', 'searchable' => false, 'fk' => [], 'active' => false],
            'resource' => $this->resource,
            'columns' => $cols = $this->columns(),
            'fkLabels' => [],
            'title' => 'Menus',
        ]);
    }

    public function create()
    {
        return view('admin.crud.form', [
            'item' => new MenuItem(['location' => 'header', 'is_active' => true, 'position' => 0, 'url' => '/']),
            'resource' => $this->resource,
            'fields' => $this->fields(),
            'title' => 'Create Menu Item',
        ]);
    }

    public function store(Request $request)
    {
        MenuItem::create($this->payload($request));
        return redirect()->route('admin.menus.index')->with('status', 'Menu item created.');
    }

    public function show($id)
    {
        $item = MenuItem::findOrFail($id);
        return view('admin.crud.show', ['item' => $item, 'resource' => $this->resource, 'fields' => $this->fields($item)]);
    }

    public function edit($id)
    {
        $item = MenuItem::findOrFail($id);
        return view('admin.crud.form', [
            'item' => $item,
            'resource' => $this->resource,
            'fields' => $this->fields($item),
            'title' => 'Edit Menu Item',
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = MenuItem::findOrFail($id);
        $data = $this->payload($request);
        // A top-level submenu can't be its own ancestor; ignore self as parent.
        if ((int) ($data['parent_id'] ?? 0) === (int) $id) {
            $data['parent_id'] = null;
        }
        $item->update($data);
        return redirect()->route('admin.menus.index')->with('status', 'Menu item updated.');
    }

    public function destroy($id)
    {
        MenuItem::findOrFail($id)->delete();
        return back()->with('status', 'Menu item deleted.');
    }
}
