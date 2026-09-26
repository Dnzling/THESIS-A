<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HolidaySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HolidayController extends Controller
{
    private function storeId(): int
    {
        return (int) Auth::user()->store_id;
    }

    public function index(Request $request)
    {
        $query = HolidaySchedule::where('store_id', $this->storeId())->orderBy('holiday_date');
        if ($request->filled('year')) $query->whereYear('holiday_date', $request->integer('year'));
        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedHoliday($request);
        $data['store_id'] = $this->storeId();
        $holiday = HolidaySchedule::updateOrCreate(
            ['store_id' => $data['store_id'], 'holiday_date' => $data['holiday_date']],
            $data
        );
        return response()->json(['success' => true, 'data' => $holiday], 201);
    }

    public function update(Request $request, int $id)
    {
        $holiday = HolidaySchedule::where('store_id', $this->storeId())->findOrFail($id);
        $holiday->update($this->validatedHoliday($request));

        return response()->json(['success' => true, 'data' => $holiday->fresh()]);
    }

    private function validatedHoliday(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'holiday_date' => 'required|date',
            'holiday_type' => 'required|in:regular,special,company,special_non_working,special_working',
            'is_working_holiday' => 'boolean',
            'rate_multiplier' => 'nullable|numeric|min:1|max:5',
            'description' => 'nullable|string|max:1000',
            'applies_to' => 'nullable|in:all,city,branches,company',
            'city' => 'nullable|required_if:applies_to,city|string|max:120',
            'branch_ids' => 'nullable|required_if:applies_to,branches|array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ]);

        if (in_array($data['holiday_type'], ['special_non_working', 'special_working'], true)) {
            $data['is_working_holiday'] = $data['holiday_type'] === 'special_working';
            $data['holiday_type'] = 'special';
        }
        if (($data['applies_to'] ?? 'all') !== 'city') $data['city'] = null;
        if (($data['applies_to'] ?? 'all') !== 'branches') $data['branch_ids'] = null;

        if (!empty($data['branch_ids'])) {
            $validCount = DB::table('branches')->where('store_id', $this->storeId())
                ->whereIn('id', $data['branch_ids'])->count();
            abort_unless($validCount === count(array_unique($data['branch_ids'])), 422, 'One or more selected branches are invalid.');
        }

        return $data;
    }

    public function destroy($id)
    {
        $holiday = HolidaySchedule::where('store_id', $this->storeId())->findOrFail($id);
        $holiday->delete();
        return response()->json(['success' => true]);
    }

    public function importPh()
    {
        $year = now()->year;
        $response = Http::timeout(10)->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/PH");
        if (!$response->successful()) return response()->json(['message' => 'Unable to fetch Philippine holidays.'], 502);
        $count = 0;
        foreach ($response->json() as $holiday) {
            HolidaySchedule::updateOrCreate(['store_id' => $this->storeId(), 'holiday_date' => $holiday['date']], ['name' => $holiday['localName'] ?? $holiday['name'], 'holiday_type' => !empty($holiday['global']) ? 'regular' : 'special', 'is_working_holiday' => false, 'rate_multiplier' => 2, 'store_id' => $this->storeId()]);
            $count++;
        }
        return response()->json(['success' => true, 'message' => "$count Philippine holiday(s) imported."]);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $headers = array_map(fn ($value) => strtolower(trim($value)), fgetcsv($handle) ?: []);
        $required = ['name', 'holiday_date'];
        if (count(array_diff($required, $headers))) return response()->json(['message' => 'CSV must include name and holiday_date columns.'], 422);
        $count = 0;
        DB::transaction(function () use ($handle, $headers, &$count) {
            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row)) === 0) continue;
                $item = array_combine($headers, array_pad($row, count($headers), null));
                if (empty($item['name']) || empty($item['holiday_date'])) continue;
                HolidaySchedule::updateOrCreate(
                    ['store_id' => $this->storeId(), 'holiday_date' => $item['holiday_date']],
                    ['name' => $item['name'], 'holiday_type' => $item['holiday_type'] ?? 'regular', 'is_working_holiday' => filter_var($item['is_working_holiday'] ?? false, FILTER_VALIDATE_BOOLEAN), 'rate_multiplier' => $item['rate_multiplier'] ?? 2, 'description' => $item['description'] ?? null, 'store_id' => $this->storeId()]
                );
                $count++;
            }
        });
        fclose($handle);
        return response()->json(['success' => true, 'message' => "$count holiday(s) imported."]);
    }
}
