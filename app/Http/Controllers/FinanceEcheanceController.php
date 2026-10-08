<?php

namespace App\Http\Controllers;

use App\Models\Exportation;
use App\Models\FactureFournisseur;
use App\Models\FactureLocale;
use App\Models\ReglementLigne;
use App\Services\FinanceService;
use App\Services\TabularExport;
use Illuminate\Http\Request;

class FinanceEcheanceController extends Controller
{
    public function __construct(private FinanceService $finance, private TabularExport $export)
    {
    }

    public function index(Request $request)
    {
        $sens = $request->get('sens') === 'fournisseur' ? 'fournisseur' : 'client';
        $horizon = max(1, min(90, (int) $request->get('horizon', 7)));
        $rows = $sens === 'fournisseur'
            ? $this->supplierRows()
            : $this->customerRows();

        $rows = array_map(fn ($row) => $this->finance->decorateDeadline($row, $horizon), $rows);

        if ($search = mb_strtolower((string) $request->get('search'))) {
            $rows = array_values(array_filter($rows, function ($row) use ($search) {
                return str_contains(mb_strtolower(($row['numero'] ?? '') . ' ' . ($row['party_nom'] ?? '')), $search);
            }));
        }
        if ($request->filled('party_id')) {
            $id = $request->integer('party_id');
            $rows = array_values(array_filter($rows, fn ($row) => (int) $row['party_id'] === $id));
        }
        if ($request->filled('from')) {
            $rows = array_values(array_filter($rows, fn ($row) => ($row['echeance'] ?? '') >= $request->get('from')));
        }
        if ($request->filled('to')) {
            $rows = array_values(array_filter($rows, fn ($row) => ($row['echeance'] ?? '') <= $request->get('to')));
        }
        if ($aging = $request->get('aging')) {
            $rows = array_values(array_filter($rows, fn ($row) => $row['aging'] === $aging));
        }
        if ($etat = $request->get('etat')) {
            $rows = array_values(array_filter($rows, function ($row) use ($etat) {
                return match ($etat) {
                    'bientot' => $row['echeance_proche'],
                    'retard' => $row['en_retard'],
                    'impayee' => $row['statut_paiement'] === 'impayee',
                    'partielle' => $row['statut_paiement'] === 'partielle',
                    'payee' => $row['statut_paiement'] === 'payee',
                    default => true,
                };
            }));
        }

        $labels = $this->finance->agingLabels();
        $aging = [];
        foreach ($labels as $code => $label) {
            $slice = array_filter($rows, fn ($row) => $row['aging'] === $code && $row['statut_paiement'] !== 'payee');
            if ($code === 'reglee') {
                $slice = array_filter($rows, fn ($row) => $row['statut_paiement'] === 'payee');
            }
            $aging[] = [
                'code' => $code,
                'label' => $label,
                'nombre' => count($slice),
                'reste' => round(array_sum(array_column($slice, 'reste')), 2),
            ];
        }

        $summary = [
            'bientot' => round(array_sum(array_map(fn ($r) => $r['echeance_proche'] ? $r['reste'] : 0, $rows)), 2),
            'retard' => round(array_sum(array_map(fn ($r) => $r['en_retard'] ? $r['reste'] : 0, $rows)), 2),
            'impaye' => round(array_sum(array_map(fn ($r) => in_array($r['statut_paiement'], ['impayee', 'partielle'], true) ? $r['reste'] : 0, $rows)), 2),
            'partiel' => count(array_filter($rows, fn ($r) => $r['statut_paiement'] === 'partielle')),
            'paye' => count(array_filter($rows, fn ($r) => $r['statut_paiement'] === 'payee')),
        ];

        if ($request->filled('format')) {
            $body = array_map(fn ($row) => [
                $row['numero'], $row['party_nom'], $row['date'], $row['echeance'], $row['total'],
                $row['montant_regle'], $row['reste'], $row['jours_retard'], $row['aging_label'],
                $row['statut_paiement'], $row['devise'],
            ], $rows);

            return $this->export->download($request->get('format'), 'echeances-' . $sens, [
                'title' => $sens === 'fournisseur' ? 'Échéances fournisseurs' : 'Échéances clients',
                'generated_at' => now()->format('d/m/Y H:i'),
                'company' => $this->finance->company(),
                'kpis' => [
                    ['label' => 'À échéance proche', 'value' => $summary['bientot']],
                    ['label' => 'En retard', 'value' => $summary['retard']],
                    ['label' => 'Reste à payer', 'value' => $summary['impaye']],
                ],
                'sections' => [[
                    'title' => 'Échéances',
                    'headers' => ['Facture', 'Tiers', 'Date', 'Échéance', 'Total', 'Réglé', 'Reste', 'Jours de retard', 'Ancienneté', 'Statut', 'Devise'],
                    'rows' => $body,
                ]],
            ]);
        }

        return response()->json([
            'data' => array_values($rows),
            'aging' => $aging,
            'summary' => $summary,
            'labels' => $labels,
            'horizon' => $horizon,
        ]);
    }

    private function customerRows(): array
    {
        $rows = [];
        $factures = FactureLocale::with('client')->whereNotIn('statut', ['brouillon', 'annulee'])->orderBy('echeance')->get();
        foreach ($factures as $facture) {
            $invoice = $this->finance->invoice(ReglementLigne::LOCALE, $facture->id);
            if ($invoice) {
                $rows[] = $invoice;
            }
        }
        $exports = Exportation::query()->whereNotNull('numero_facture')->where('numero_facture', '!=', '')->pluck('id');
        foreach ($exports as $id) {
            $invoice = $this->finance->invoice(ReglementLigne::EXPORT, (int) $id);
            if ($invoice) {
                $rows[] = $invoice;
            }
        }

        return $rows;
    }

    private function supplierRows(): array
    {
        $rows = [];
        $factures = FactureFournisseur::with('fournisseur')->where('statut', '!=', 'brouillon')->orderBy('echeance')->get();
        foreach ($factures as $facture) {
            $invoice = $this->finance->invoice(ReglementLigne::FOURNISSEUR, $facture->id);
            if ($invoice) {
                $rows[] = $invoice;
            }
        }

        return $rows;
    }
}
