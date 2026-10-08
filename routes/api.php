<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\ExportationController;
use App\Http\Controllers\DossierEmballageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AchatController;
use App\Http\Controllers\FactureFournisseurController;
use App\Http\Controllers\BonReceptionController;
use App\Http\Controllers\StockMouvementController;
use App\Http\Controllers\StockSituationController;
use App\Http\Controllers\InventaireController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\OrdreProductionController;
use App\Http\Controllers\LivraisonController;
use App\Http\Controllers\BonLivraisonController;
use App\Http\Controllers\FactureLocaleController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\NoteCreditController;
use App\Http\Controllers\SuiviVentesController;
use App\Http\Controllers\ExportateurController;
use App\Http\Controllers\FinanceReglementController;
use App\Http\Controllers\FinanceEcheanceController;
use App\Http\Controllers\FinanceLettrageController;
use App\Http\Controllers\FinanceBanqueController;
use App\Http\Controllers\RapportController;

Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
Route::get('/search', [DashboardController::class, 'search']);

Route::prefix('clients')->group(function () {
    Route::get('/', [ClientController::class, 'index']);
    Route::post('/', [ClientController::class, 'store']);
    Route::get('/{client}', [ClientController::class, 'show']);
    Route::put('/{client}', [ClientController::class, 'update']);
    Route::delete('/{client}', [ClientController::class, 'destroy']);
    Route::post('/{client}/articles', [ClientController::class, 'assignArticles']);
    Route::get('/{client}/articles', [ClientController::class, 'getArticles']);
    Route::post('/{client}/toggle-active', [ClientController::class, 'toggleActive']);
    Route::post('/{client}/convert', [ClientController::class, 'convert']);
});

Route::prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::post('/', [ArticleController::class, 'store']);
    Route::get('/stats', [ArticleController::class, 'stats']);
    Route::get('/{article}', [ArticleController::class, 'show']);
    Route::put('/{article}', [ArticleController::class, 'update']);
    Route::delete('/{article}', [ArticleController::class, 'destroy']);
    Route::post('/{article}/duplicate', [ArticleController::class, 'duplicate']);
    Route::post('/{article}/toggle-active', [ArticleController::class, 'toggleActive']);
});

Route::prefix('fournisseurs')->group(function () {
    Route::get('/', [FournisseurController::class, 'index']);
    Route::post('/', [FournisseurController::class, 'store']);
    Route::get('/{fournisseur}', [FournisseurController::class, 'show']);
    Route::put('/{fournisseur}', [FournisseurController::class, 'update']);
    Route::delete('/{fournisseur}', [FournisseurController::class, 'destroy']);
});

Route::prefix('parametres')->group(function () {
    Route::get('/', [ParametreController::class, 'index']);
    Route::get('/types', [ParametreController::class, 'getTypes']);
    Route::get('/all-for-forms', [ParametreController::class, 'getAllForForms']);
    Route::get('/type/{type}', [ParametreController::class, 'getByType']);
    Route::get('/sous-familles/{familleId}', [ParametreController::class, 'getSousFamilles']);
    Route::post('/', [ParametreController::class, 'store']);
    Route::put('/{parametre}', [ParametreController::class, 'update']);
    Route::delete('/{parametre}', [ParametreController::class, 'destroy']);
    Route::post('/{parametre}/toggle-active', [ParametreController::class, 'toggleActive']);
    Route::post('/reorder', [ParametreController::class, 'reorder']);
});

Route::get('/document-types', [ExportationController::class, 'documentTypes']);
Route::get('/exportations-meta', [ExportationController::class, 'meta']);
Route::get('/documents/{document}/download', [ExportationController::class, 'downloadDocument']);

Route::prefix('exportations')->group(function () {
    Route::get('/', [ExportationController::class, 'index']);
    Route::post('/', [ExportationController::class, 'store']);
    Route::post('/from-commande/{commande}', [ExportationController::class, 'fromCommande']);
    Route::get('/{exportation}', [ExportationController::class, 'show']);
    Route::put('/{exportation}', [ExportationController::class, 'update']);
    Route::delete('/{exportation}', [ExportationController::class, 'destroy']);
    Route::post('/{exportation}/to-facture', [ExportationController::class, 'toFacture']);
    Route::post('/{exportation}/statut', [ExportationController::class, 'changeStatut']);
    Route::post('/{exportation}/documents', [ExportationController::class, 'generateDocument']);
    Route::post('/{exportation}/pieces', [ExportationController::class, 'uploadPiece']);
});

Route::prefix('emballages')->group(function () {
    Route::get('/', [DossierEmballageController::class, 'index']);
    Route::get('/par-client', [DossierEmballageController::class, 'parClient']);
    Route::get('/{dossierEmballage}', [DossierEmballageController::class, 'show']);
    Route::put('/{dossierEmballage}', [DossierEmballageController::class, 'update']);
    Route::post('/{dossierEmballage}/retours', [DossierEmballageController::class, 'storeRetour']);
});

Route::delete('/retours/{retour}', [DossierEmballageController::class, 'destroyRetour']);

Route::get('/achats/meta', [AchatController::class, 'meta']);
Route::get('/achats/pieces/{piece}', [AchatController::class, 'downloadPiece']);
Route::prefix('achats')->group(function () {
    Route::get('/', [AchatController::class, 'index']);
    Route::post('/', [AchatController::class, 'store']);
    Route::get('/{achat}', [AchatController::class, 'show']);
    Route::put('/{achat}', [AchatController::class, 'update']);
    Route::delete('/{achat}', [AchatController::class, 'destroy']);
    Route::post('/{achat}/statut', [AchatController::class, 'changeStatut']);
    Route::post('/{achat}/receptions', [AchatController::class, 'storeReception']);
    Route::post('/{achat}/pieces', [AchatController::class, 'uploadPiece']);
});

Route::get('/factures-fournisseurs/suivi-reglementaire', [FactureFournisseurController::class, 'suiviReglementaire']);
Route::prefix('factures-fournisseurs')->group(function () {
    Route::get('/', [FactureFournisseurController::class, 'index']);
    Route::post('/', [FactureFournisseurController::class, 'store']);
    Route::get('/{facture}', [FactureFournisseurController::class, 'show']);
    Route::put('/{facture}', [FactureFournisseurController::class, 'update']);
    Route::delete('/{facture}', [FactureFournisseurController::class, 'destroy']);
    Route::post('/{facture}/suivi', [FactureFournisseurController::class, 'updateSuivi']);
    Route::post('/{facture}/pieces', [FactureFournisseurController::class, 'uploadPiece']);
});

// 5. Stock
Route::get('/stock/receptions/meta', [BonReceptionController::class, 'meta']);
Route::get('/stock/receptions/pieces/{piece}', [BonReceptionController::class, 'downloadPiece']);
Route::prefix('stock/receptions')->group(function () {
    Route::get('/', [BonReceptionController::class, 'index']);
    Route::post('/', [BonReceptionController::class, 'store']);
    Route::get('/{reception}', [BonReceptionController::class, 'show']);
    Route::put('/{reception}', [BonReceptionController::class, 'update']);
    Route::delete('/{reception}', [BonReceptionController::class, 'destroy']);
    Route::post('/{reception}/valider', [BonReceptionController::class, 'valider']);
    Route::post('/{reception}/pieces', [BonReceptionController::class, 'uploadPiece']);
});

Route::get('/stock/mouvements/meta', [StockMouvementController::class, 'meta']);
Route::prefix('stock/mouvements')->group(function () {
    Route::get('/', [StockMouvementController::class, 'index']);
    Route::post('/', [StockMouvementController::class, 'store']);
});

Route::get('/stock/situation/meta', [StockSituationController::class, 'meta']);
Route::get('/stock/situation/resume', [StockSituationController::class, 'resume']);
Route::get('/stock/situation/article/{articleId}', [StockSituationController::class, 'byArticle']);
Route::get('/stock/situation', [StockSituationController::class, 'index']);

Route::get('/stock/inventaires/meta', [InventaireController::class, 'meta']);
Route::prefix('stock/inventaires')->group(function () {
    Route::get('/', [InventaireController::class, 'index']);
    Route::post('/', [InventaireController::class, 'store']);
    Route::get('/{inventaire}', [InventaireController::class, 'show']);
    Route::put('/{inventaire}', [InventaireController::class, 'update']);
    Route::delete('/{inventaire}', [InventaireController::class, 'destroy']);
    Route::post('/{inventaire}/valider', [InventaireController::class, 'valider']);
});

// 6. Ventes — Commandes / Production / Logistique
Route::get('/commandes/meta', [CommandeController::class, 'meta']);
Route::get('/commandes/pieces/{piece}', [CommandeController::class, 'downloadPiece']);
Route::prefix('commandes')->group(function () {
    Route::get('/', [CommandeController::class, 'index']);
    Route::post('/', [CommandeController::class, 'store']);
    Route::get('/{commande}', [CommandeController::class, 'show']);
    Route::put('/{commande}', [CommandeController::class, 'update']);
    Route::delete('/{commande}', [CommandeController::class, 'destroy']);
    Route::post('/{commande}/statut', [CommandeController::class, 'changeStatut']);
    Route::post('/{commande}/pieces', [CommandeController::class, 'uploadPiece']);
    Route::post('/{commande}/marquer-a-facturer', [CommandeController::class, 'marquerAFacturer']);
    Route::get('/{commande}/documents', [CommandeController::class, 'listDocuments']);
    Route::post('/{commande}/documents', [CommandeController::class, 'generateDocument']);
});

Route::get('/productions/meta', [OrdreProductionController::class, 'meta']);
Route::prefix('productions')->group(function () {
    Route::get('/', [OrdreProductionController::class, 'index']);
    Route::post('/', [OrdreProductionController::class, 'store']);
    Route::get('/{ordreProduction}', [OrdreProductionController::class, 'show']);
    Route::put('/{ordreProduction}', [OrdreProductionController::class, 'update']);
    Route::delete('/{ordreProduction}', [OrdreProductionController::class, 'destroy']);
    Route::post('/{ordreProduction}/statut', [OrdreProductionController::class, 'changeStatut']);
});

Route::get('/livraisons/meta', [LivraisonController::class, 'meta']);
Route::get('/livraisons/pieces/{piece}', [LivraisonController::class, 'downloadPiece']);
Route::prefix('livraisons')->group(function () {
    Route::get('/', [LivraisonController::class, 'index']);
    Route::post('/', [LivraisonController::class, 'store']);
    Route::get('/{livraison}', [LivraisonController::class, 'show']);
    Route::put('/{livraison}', [LivraisonController::class, 'update']);
    Route::delete('/{livraison}', [LivraisonController::class, 'destroy']);
    Route::post('/{livraison}/statut', [LivraisonController::class, 'changeStatut']);
    Route::post('/{livraison}/pieces', [LivraisonController::class, 'uploadPiece']);
    Route::post('/{livraison}/cin-scan', [LivraisonController::class, 'uploadCinScan']);
    Route::delete('/{livraison}/conteneurs/{conteneur}', [LivraisonController::class, 'destroyConteneur']);
});

// Bons de livraison (ventes locales) — transformation commande → BL
Route::get('/bons-livraison/meta', [BonLivraisonController::class, 'meta']);
Route::prefix('bons-livraison')->group(function () {
    Route::get('/', [BonLivraisonController::class, 'index']);
    Route::post('/', [BonLivraisonController::class, 'store']);
    Route::post('/from-commande/{commande}', [BonLivraisonController::class, 'fromCommande']);
    Route::get('/{bonLivraison}', [BonLivraisonController::class, 'show']);
    Route::put('/{bonLivraison}', [BonLivraisonController::class, 'update']);
    Route::delete('/{bonLivraison}', [BonLivraisonController::class, 'destroy']);
    Route::post('/{bonLivraison}/to-facture', [BonLivraisonController::class, 'toFacture']);
});

// Facturation ventes locales
Route::get('/factures-locales/meta', [FactureLocaleController::class, 'meta']);
Route::prefix('factures-locales')->group(function () {
    Route::get('/', [FactureLocaleController::class, 'index']);
    Route::post('/', [FactureLocaleController::class, 'store']);
    Route::post('/from-bl/{bonLivraison}', [FactureLocaleController::class, 'fromBonLivraison']);
    Route::get('/{factureLocale}', [FactureLocaleController::class, 'show']);
    Route::put('/{factureLocale}', [FactureLocaleController::class, 'update']);
    Route::delete('/{factureLocale}', [FactureLocaleController::class, 'destroy']);
});

// Réclamations / Notes de crédit / Suivi des ventes
Route::get('/reclamations/meta', [ReclamationController::class, 'meta']);
Route::get('/reclamations/stats', [ReclamationController::class, 'stats']);
Route::get('/reclamations/pieces/{piece}', [ReclamationController::class, 'downloadPiece']);
Route::prefix('reclamations')->group(function () {
    Route::get('/', [ReclamationController::class, 'index']);
    Route::post('/', [ReclamationController::class, 'store']);
    Route::get('/{reclamation}', [ReclamationController::class, 'show']);
    Route::put('/{reclamation}', [ReclamationController::class, 'update']);
    Route::delete('/{reclamation}', [ReclamationController::class, 'destroy']);
    Route::post('/{reclamation}/statut', [ReclamationController::class, 'changeStatut']);
    Route::post('/{reclamation}/pieces', [ReclamationController::class, 'uploadPiece']);
});

Route::get('/notes-credit/meta', [NoteCreditController::class, 'meta']);
Route::get('/notes-credit/pieces/{piece}', [NoteCreditController::class, 'downloadPiece']);
Route::prefix('notes-credit')->group(function () {
    Route::get('/', [NoteCreditController::class, 'index']);
    Route::post('/', [NoteCreditController::class, 'store']);
    Route::get('/{noteCredit}', [NoteCreditController::class, 'show']);
    Route::put('/{noteCredit}', [NoteCreditController::class, 'update']);
    Route::delete('/{noteCredit}', [NoteCreditController::class, 'destroy']);
    Route::post('/{noteCredit}/pieces', [NoteCreditController::class, 'uploadPiece']);
});

Route::get('/suivi-ventes/meta', [SuiviVentesController::class, 'meta']);
Route::get('/suivi-ventes', [SuiviVentesController::class, 'index']);

// 7. Finance
Route::get('/finance/meta', [FinanceReglementController::class, 'meta']);
Route::get('/finance/tiers', [FinanceReglementController::class, 'tiers']);
Route::get('/finance/factures-ouvertes', [FinanceReglementController::class, 'facturesOuvertes']);
Route::get('/finance/reglements', [FinanceReglementController::class, 'index']);
Route::post('/finance/reglements', [FinanceReglementController::class, 'store']);
Route::get('/finance/reglements/{reglement}', [FinanceReglementController::class, 'show']);
Route::put('/finance/reglements/{reglement}', [FinanceReglementController::class, 'update']);
Route::delete('/finance/reglements/{reglement}', [FinanceReglementController::class, 'destroy']);

Route::get('/finance/echeances', [FinanceEcheanceController::class, 'index']);

Route::get('/finance/lettrage', [FinanceLettrageController::class, 'index']);
Route::post('/finance/lettrage/auto', [FinanceLettrageController::class, 'auto']);
Route::post('/finance/lettrage', [FinanceLettrageController::class, 'store']);
Route::delete('/finance/lettrage/{ligne}', [FinanceLettrageController::class, 'destroy']);

Route::get('/finance/banque/comptes', [FinanceBanqueController::class, 'index']);
Route::post('/finance/banque/comptes', [FinanceBanqueController::class, 'store']);
Route::get('/finance/banque/comptes/{compte}', [FinanceBanqueController::class, 'show']);
Route::put('/finance/banque/comptes/{compte}', [FinanceBanqueController::class, 'update']);
Route::delete('/finance/banque/comptes/{compte}', [FinanceBanqueController::class, 'destroy']);
Route::get('/finance/banque/comptes/{compte}/export', [FinanceBanqueController::class, 'exportMouvements']);
Route::post('/finance/banque/comptes/{compte}/mouvements', [FinanceBanqueController::class, 'storeMouvement']);
Route::post('/finance/banque/comptes/{compte}/import', [FinanceBanqueController::class, 'import']);
Route::delete('/finance/banque/mouvements/{mouvement}', [FinanceBanqueController::class, 'destroyMouvement']);
Route::get('/finance/banque/comptes/{compte}/rapprochement', [FinanceBanqueController::class, 'rapprochement']);
Route::post('/finance/banque/comptes/{compte}/rapprochement/auto', [FinanceBanqueController::class, 'autoRapprochement']);
Route::post('/finance/banque/comptes/{compte}/rapprochement', [FinanceBanqueController::class, 'storeRapprochement']);
Route::delete('/finance/banque/rapprochement/{ligne}', [FinanceBanqueController::class, 'destroyRapprochement']);

// 8. Rapports
Route::get('/rapports/meta', [RapportController::class, 'meta']);
Route::get('/rapports/ventes', [RapportController::class, 'ventes']);
Route::get('/rapports/achats', [RapportController::class, 'achats']);
Route::get('/rapports/stock', [RapportController::class, 'stock']);
Route::get('/rapports/finance', [RapportController::class, 'finance']);

Route::prefix('exportateurs')->group(function () {
    Route::get('/', [ExportateurController::class, 'index']);
    Route::post('/', [ExportateurController::class, 'store']);
    Route::get('/{exportateur}', [ExportateurController::class, 'show']);
    Route::put('/{exportateur}', [ExportateurController::class, 'update']);
    Route::delete('/{exportateur}', [ExportateurController::class, 'destroy']);
    Route::post('/{exportateur}/toggle-active', [ExportateurController::class, 'toggleActive']);
    Route::post('/{exportateur}/set-primaire', [ExportateurController::class, 'setPrimaire']);
});
