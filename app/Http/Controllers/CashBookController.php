<?php

namespace App\Http\Controllers;

use App\Services\CashBookService;
use App\Services\ExcelExportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class CashBookController extends Controller
{
    public function __construct(private CashBookService $book) {}

    public function index(Request $request)
    {
        return view('cashbook.index', $this->book->build($this->month($request)));
    }

    public function export(Request $request, ExcelExportService $excel)
    {
        $data = $this->book->build($this->month($request));

        return $excel->exportCashBook($data, 'libro_de_caja_' . $data['month']->format('Y-m') . '.xlsx');
    }

    private function month(Request $request): CarbonImmutable
    {
        $value = (string) $request->query('month', '');
        $now = CarbonImmutable::now('America/Santiago');

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            $month = CarbonImmutable::createFromFormat('Y-m-d', $value . '-01', 'America/Santiago')->startOfMonth();

            return $month->gt($now) ? $now->startOfMonth() : $month;
        }

        return $now->startOfMonth();
    }
}
