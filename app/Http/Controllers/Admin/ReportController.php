<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reportService): View
    {
        [$from, $to] = $this->range($request);

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'report' => $reportService->build($from, $to),
        ]);
    }

    public function export(Request $request, ReportService $reportService): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $rows = $reportService->exportRows($from, $to);
        $filename = 'laporan-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Tanggal',
                'Jenis',
                'Nomor',
                'Pelanggan',
                'Pembayaran',
                'Status',
                'Subtotal',
                'Diskon',
                'Ongkir',
                'Total',
                'HPP',
                'Laba Kotor',
            ]);

            foreach ($rows as $row) {
                fputcsv($output, array_map(fn (string|float $value) => $this->csvCell($value), $row));
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $from = isset($data['start_date'])
            ? Carbon::parse($data['start_date'])->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $to = isset($data['end_date'])
            ? Carbon::parse($data['end_date'])->endOfDay()
            : now()->endOfDay();

        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal awal tidak boleh setelah tanggal akhir.',
            ]);
        }

        return [$from, $to];
    }

    private function csvCell(string|float $value): string
    {
        $value = (string) $value;

        if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
