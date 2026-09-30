<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StockLocation;
use App\Models\StockMouvement;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockMouvementController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request)
    {
        $query = StockMouvement::with(['article', 'location', 'fromLocation', 'toLocation', 'user'])
            ->where('annule', false)
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('document_ref', 'like', "%{$search}%")
                    ->orWhere('lot', 'like', "%{$search}%")
                    ->orWhere('commentaire', 'like', "%{$search}%")
                    ->orWhereHas('article', function ($a) use ($search) {
                        $a->where('code_article', 'like', "%{$search}%")
                            ->orWhere('designation', 'like', "%{$search}%");
                    });
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($articleId = $request->get('article_id')) {
            $query->where('article_id', $articleId);
        }

        if ($from = $request->get('date_from')) {
            $query->whereDate('date_mouvement', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('date_mouvement', '<=', $to);
        }

        return response()->json($query->paginate($request->get('per_page', 30)));
    }

    public function meta()
    {
        return response()->json([
            'types' => StockMouvement::TYPES,
            'type_labels' => StockMouvement::TYPE_LABELS,
            'emplacements' => StockLocation::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:' . implode(',', StockMouvement::TYPES),
            'date_mouvement' => 'nullable|date',
            'article_id' => 'required|exists:articles,id',
            'quantite' => 'required|numeric|gt:0',
            'unite' => 'nullable|string|max:30',
            'lot' => 'nullable|string|max:100',
            'stock_location_id' => 'nullable|exists:stock_locations,id',
            'from_location_id' => 'nullable|exists:stock_locations,id',
            'to_location_id' => 'nullable|exists:stock_locations,id',
            'document_ref' => 'nullable|string|max:100',
            'commentaire' => 'nullable|string',
        ]);

        if ($data['type'] === 'transfert') {
            $request->validate([
                'from_location_id' => 'required|exists:stock_locations,id',
                'to_location_id' => 'required|exists:stock_locations,id|different:from_location_id',
            ]);
        }

        if (in_array($data['type'], ['ajustement'], true) && $request->has('signe') && $request->get('signe') === '-') {
            $m = $this->stock->applyAdjustment(
                (int) $data['article_id'],
                -1 * abs((float) $data['quantite']),
                $data['stock_location_id'] ?? null,
                $data['lot'] ?? null,
                'ajustement',
                [
                    'date_mouvement' => $data['date_mouvement'] ?? now()->toDateString(),
                    'unite' => $data['unite'] ?? null,
                    'document_ref' => $data['document_ref'] ?? null,
                    'commentaire' => $data['commentaire'] ?? 'Ajustement manuel',
                ],
                $request->user()?->id
            );
        } else {
            $m = $this->stock->move($data, $request->user()?->id);
        }

        AuditLog::record($m, 'create', 'type', null, $m->type, $request->user()?->id);

        return response()->json($m, 201);
    }
}
