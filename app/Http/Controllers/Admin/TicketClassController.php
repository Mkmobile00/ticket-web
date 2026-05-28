<?php

namespace App\Http\Controllers\Admin;

use App\Models\Showtime;
use App\Models\TicketClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketClassController extends AdminController
{
    protected string $modelClass = \App\Models\TicketClass::class;
    protected string $resource = 'ticket-class';

    /** Seat rows available on the current ticket class's showtime screen. */
    protected ?array $seatRows = null;

    protected function fields(): array
    {
        $fields = parent::fields();
        foreach ($fields as &$f) {
            if ($f['name'] === 'seat_rows') {
                $f['type'] = 'rows';
                if ($this->seatRows) {
                    $f['rows'] = $this->seatRows; // only show rows the screen actually has
                }
            }
            if ($f['name'] === 'showtime_id') {
                $f['options'] = $this->showtimeOptions();
            }
        }
        return $fields;
    }

    /** Rows that physically exist on a showtime's screen, plus any already-selected. */
    private function rowsFor(?Showtime $showtime, array $selected = []): ?array
    {
        $layout = $showtime?->screen?->seat_layout;
        $rows = is_array($layout) ? ($layout['rows'] ?? []) : [];
        if (! $rows) {
            return null; // unknown -> form falls back to A–Z
        }
        // Never drop rows that are already assigned, even if the screen changed.
        $merged = array_values(array_unique(array_merge($rows, $selected)));
        sort($merged);
        return $merged;
    }

    protected function fkLabelMap(array $columns): array
    {
        $map = parent::fkLabelMap($columns);
        if (in_array('showtime_id', $columns, true)) {
            $map['showtime_id'] = Showtime::with('movie', 'screen.cinema')->get()
                ->mapWithKeys(fn (Showtime $s) => [$s->id => Str::limit($s->label, 60)])
                ->all();
        }
        return $map;
    }

    /** [id => "Movie — Cinema (Screen) · Date Time (#id)"] for the showtime dropdown. */
    private function showtimeOptions(): array
    {
        return Showtime::with('movie', 'screen.cinema')
            ->orderBy('show_date')->orderBy('show_time')
            ->get()
            ->mapWithKeys(fn (Showtime $s) => [$s->id => $s->label . ' (#' . $s->id . ')'])
            ->all();
    }

    protected function rules(?Model $item = null): array
    {
        $rules = parent::rules($item);
        $rules['seat_rows'] = 'nullable|array';
        $rules['seat_rows.*'] = 'string|size:1|regex:/^[A-Z]$/';
        return $rules;
    }

    public function index()
    {
        $items = ($this->modelClass)::query()->latest('id')->paginate(15);
        return view('admin.crud.index', [
            'items' => $items,
            'resource' => $this->resource,
            'columns' => $cols = $this->columns(),
            'fkLabels' => $this->fkLabelMap($cols),
            'title' => ucwords(str_replace('-', ' ', $this->resource)) . 's',
        ]);
    }

    public function create()
    {
        return view('admin.crud.form', [
            'item' => new ($this->modelClass),
            'resource' => $this->resource,
            'fields' => $this->fields(),
            'title' => 'Create ' . ucwords(str_replace('-', ' ', $this->resource)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['seat_rows'] = array_values(array_unique((array) $request->input('seat_rows', [])));
        ($this->modelClass)::create($data);
        return redirect()->route('admin.' . \Illuminate\Support\Str::plural($this->resource) . '.index')->with('status', 'Created.');
    }

    public function show($id)
    {
        $item = ($this->modelClass)::with('showtime.screen')->findOrFail($id);
        $this->seatRows = $this->rowsFor($item->showtime, (array) $item->seat_rows);
        return view('admin.crud.show', ['item' => $item, 'resource' => $this->resource, 'fields' => $this->fields()]);
    }

    public function edit($id)
    {
        $item = ($this->modelClass)::with('showtime.screen')->findOrFail($id);
        $this->seatRows = $this->rowsFor($item->showtime, (array) $item->seat_rows);
        return view('admin.crud.form', [
            'item' => $item,
            'resource' => $this->resource,
            'fields' => $this->fields(),
            'title' => 'Edit ' . ucwords(str_replace('-', ' ', $this->resource)),
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        $data = $request->validate($this->rules($item));
        $data['seat_rows'] = array_values(array_unique((array) $request->input('seat_rows', [])));
        $item->update($data);
        return redirect()->route('admin.' . \Illuminate\Support\Str::plural($this->resource) . '.index')->with('status', 'Updated.');
    }

    public function destroy($id)
    {
        ($this->modelClass)::findOrFail($id)->delete();
        return back()->with('status', 'Deleted.');
    }
}




