<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8 no-print">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Facturation locale</h1>
          <p class="text-gray-600 text-sm mt-1">
            Factures ventes locales — depuis bon de livraison ou saisie directe
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button @click="openFromBl" class="px-5 py-2.5 border border-teal-600 text-teal-700 rounded-lg hover:bg-teal-50 font-medium">
            Depuis un BL
          </button>
          <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
            + Nouvelle facture
          </button>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="N° facture, client, BL, commande…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous les statuts</option>
          <option value="brouillon">Brouillon</option>
          <option value="validee">Validée</option>
          <option value="payee">Payée</option>
          <option value="annulee">Annulée</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N° Facture</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">BL</th>
              <th class="py-3 px-4">Commande</th>
              <th class="py-3 px-4">HT</th>
              <th class="py-3 px-4">TTC</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucune facture locale</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_facture) }}</td>
              <td class="py-3 px-4">{{ item.client?.nom || '—' }}</td>
              <td class="py-3 px-4">{{ item.numero_bl || item.bon_livraison?.numero || '—' }}</td>
              <td class="py-3 px-4">{{ item.numero_commande || item.commande?.numero || '—' }}</td>
              <td class="py-3 px-4">{{ formatMoney(item.total_ht) }} {{ item.devise }}</td>
              <td class="py-3 px-4 font-medium">{{ formatMoney(item.total_ttc) }} {{ item.devise }}</td>
              <td class="py-3 px-4">
                <span :class="badgeClass(item.statut)" class="px-2 py-1 rounded text-xs font-medium">{{ labelStatut(item.statut) }}</span>
              </td>
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <button type="button" title="Actions" @click.stop="toggleMenu(item, $event)" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" /></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <teleport to="body">
        <div v-if="menu.item" class="fixed inset-0 z-[60]" @click="closeMenu" @wheel="closeMenu"></div>
        <div v-if="menu.item" class="fixed z-[61] w-64 bg-white border border-gray-200 rounded-lg shadow-lg py-1 text-sm text-gray-700" :style="{ top: menu.top + 'px', left: menu.left + 'px' }">
          <button type="button" @click="runMenu((it) => openDetail(it))" class="w-full flex items-center gap-3 px-4 py-2 text-left hover:bg-gray-50 disabled:opacity-50 text-teal-700">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            <span>Ouvrir</span>
          </button>
          <button type="button" :disabled="downloadingId === menu.item.id" @click="runMenu((it) => downloadPdf(it))" class="w-full flex items-center gap-3 px-4 py-2 text-left hover:bg-gray-50 disabled:opacity-50 text-sky-700">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            <span>Exporter en PDF</span>
          </button>
          <div class="border-t border-gray-100 my-1"></div>
          <button type="button" @click="runMenu((it) => openDelete(it))" class="w-full flex items-center gap-3 px-4 py-2 text-left hover:bg-gray-50 disabled:opacity-50 text-red-600">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            <span>Supprimer</span>
          </button>
        </div>
      </teleport>
      <!-- Suppression -->
      <div v-if="deleteTarget" class="app-modal-overlay">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">Supprimer la facture {{ deleteTarget.numero }}</h2>
            <button type="button" class="app-modal__close" @click="deleteTarget=null" :disabled="deleting">&times;</button>
          </div>
          <div class="app-modal__body space-y-3">
            <p class="text-sm text-gray-600">Choisissez le type de suppression :</p>
            <button type="button" :disabled="deleting" @click="confirmDelete('remettre_bl')" class="w-full text-left border border-amber-300 rounded-lg p-3 hover:bg-amber-50 disabled:opacity-50">
              <div class="font-medium text-amber-700">Supprimer et remettre en bon de livraison</div>
              <div class="text-xs text-gray-500 mt-1">La facture est supprimée et son bon de livraison redevient « à facturer ».</div>
            </button>
            <button type="button" :disabled="deleting" @click="confirmDelete('definitif')" class="w-full text-left border border-red-300 rounded-lg p-3 hover:bg-red-50 disabled:opacity-50">
              <div class="font-medium text-red-700">Supprimer définitivement</div>
              <div class="text-xs text-gray-500 mt-1">La facture, son bon de livraison ET la commande d'origine sont supprimés de la base de données.</div>
            </button>
            <p v-if="deleteError" class="text-red-600 text-sm">{{ deleteError }}</p>
          </div>
          <div class="app-modal__footer">
            <button type="button" @click="deleteTarget=null" :disabled="deleting" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
          </div>
        </div>
      </div>

      <p v-if="actionError" class="mt-3 text-sm text-red-600">{{ actionError }}</p>

      <!-- Create from BL -->
      <div v-if="showFromBl" class="app-modal-overlay">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">Facture depuis un bon de livraison</h2>
            <button type="button" class="app-modal__close" @click="showFromBl=false">&times;</button>
          </div>
          <div class="app-modal__body space-y-4">
            <div>
              <label class="text-sm text-gray-600">Bon de livraison</label>
              <select v-model="blForm.bon_livraison_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                <option :value="null">Sélectionner…</option>
                <option v-for="b in blDispo" :key="b.id" :value="b.id">
                  {{ b.numero }} — {{ b.client?.nom || 'Sans client' }}
                </option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Seuls les BL non encore facturés sont listés.</p>
            </div>
            <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
          </div>
          <div class="app-modal__footer">
            <button type="button" @click="showFromBl=false" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
            <button type="button" @click="createFromBl" :disabled="saving || !blForm.bon_livraison_id" class="px-5 py-2 bg-teal-600 text-white rounded-lg disabled:opacity-50">
              {{ saving ? 'Création…' : 'Créer la facture' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Detail / Edit -->
      <div v-if="showModal && form" class="app-modal-overlay">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <div>
              <h2 class="app-modal__title">{{ form.id ? `Facture ${form.numero}` : 'Nouvelle facture locale' }}</h2>
              <p class="app-modal__subtitle" v-if="form.numero_bl">Issu du BL {{ form.numero_bl }}</p>
            </div>
            <button type="button" class="app-modal__close" @click="closeDetail">&times;</button>
          </div>

          <div class="app-modal__body">
            <div class="achat-header-grid mb-5">
              <div>
                <label class="text-sm text-gray-600">N° facture</label>
                <input :value="form.numero || 'Auto'" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 font-semibold text-teal-700" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Date</label>
                <input type="date" v-model="form.date_facture" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Client</label>
                <select v-model="form.client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Réf. client</label>
                <input v-model="form.reference_client" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">N° BL</label>
                <input v-model="form.numero_bl" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">N° commande</label>
                <input v-model="form.numero_commande" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Devise</label>
                <input v-model="form.devise" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Échéance</label>
                <input type="date" v-model="form.echeance" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Conditions paiement</label>
                <input v-model="form.conditions_paiement" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Statut</label>
                <select v-model="form.statut" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                  <option value="brouillon">Brouillon</option>
                  <option value="validee">Validée</option>
                  <option value="payee">Payée</option>
                  <option value="annulee">Annulée</option>
                </select>
              </div>
              <div class="achat-span-full">
                <label class="text-sm text-gray-600">Observations</label>
                <textarea v-model="form.observations" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div class="mb-3 flex items-center justify-between">
              <h3 class="font-semibold text-gray-900">Lignes</h3>
              <button type="button" @click="addLigne" class="text-sm text-teal-600 hover:underline">+ Ligne</button>
            </div>
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                  <tr>
                    <th class="py-2 px-2 text-left">Réf.</th>
                    <th class="py-2 px-2 text-left">Désignation</th>
                    <th class="py-2 px-2 text-right">Qté</th>
                    <th class="py-2 px-2 text-right">PU</th>
                    <th class="py-2 px-2 text-right">TVA %</th>
                    <th class="py-2 px-2 text-right">HT</th>
                    <th class="py-2 px-2 text-right">TTC</th>
                    <th class="py-2 px-2"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(ligne, idx) in form.lignes" :key="idx" class="border-t border-gray-100">
                    <td class="py-1.5 px-2">
                      <input v-model="ligne.ref_article" class="w-24 border rounded px-1.5 py-1" />
                    </td>
                    <td class="py-1.5 px-2">
                      <input v-model="ligne.designation" class="w-full min-w-[140px] border rounded px-1.5 py-1" />
                    </td>
                    <td class="py-1.5 px-2">
                      <input type="number" step="0.001" v-model.number="ligne.quantite" @input="recalcLigne(ligne)" class="w-20 border rounded px-1.5 py-1 text-right" />
                    </td>
                    <td class="py-1.5 px-2">
                      <input type="number" step="0.01" v-model.number="ligne.prix_unitaire" @input="recalcLigne(ligne)" class="w-24 border rounded px-1.5 py-1 text-right" />
                    </td>
                    <td class="py-1.5 px-2">
                      <input type="number" step="0.01" v-model.number="ligne.tva_taux" @input="recalcLigne(ligne)" class="w-16 border rounded px-1.5 py-1 text-right" />
                    </td>
                    <td class="py-1.5 px-2 text-right whitespace-nowrap">{{ formatMoney(ligne.montant_ht) }}</td>
                    <td class="py-1.5 px-2 text-right whitespace-nowrap font-medium">{{ formatMoney(ligne.montant_ttc) }}</td>
                    <td class="py-1.5 px-2 text-center">
                      <button type="button" @click="form.lignes.splice(idx,1)" class="text-red-500">&times;</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-4 flex flex-wrap justify-end gap-6 text-sm">
              <div>Total HT : <strong>{{ formatMoney(totals.ht) }} {{ form.devise }}</strong></div>
              <div>Total TVA : <strong>{{ formatMoney(totals.tva) }} {{ form.devise }}</strong></div>
              <div>Total TTC : <strong class="text-teal-700">{{ formatMoney(totals.ttc) }} {{ form.devise }}</strong></div>
            </div>

            <p v-if="error" class="text-red-600 text-sm mt-3">{{ error }}</p>
            <p v-if="message" class="text-teal-700 text-sm mt-3">{{ message }}</p>
          </div>
          <div class="app-modal__footer app-modal__footer--between">
            <button type="button" @click="printFacture" :disabled="!form.id" class="px-4 py-2 border border-gray-300 rounded-lg bg-white inline-flex items-center gap-2 disabled:opacity-50">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              Imprimer
            </button>
            <div class="flex gap-3">
              <button type="button" @click="closeDetail" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Fermer</button>
              <button type="button" @click="save" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-lg disabled:opacity-50">
                {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-if="form" class="facture-print">
    <table class="inv">
      <tr>
        <td style="width:58%;border-bottom:none;">
          <div class="inv-brand">{{ societeNom }}</div>
          <div class="inv-tagline">Facture de vente locale</div>
          <div class="inv-contact">
            <template v-if="societe?.adresse">{{ societe.adresse }}<br></template>
            <template v-if="societe?.telephone">Tél. : {{ societe.telephone }}<br></template>
            <template v-if="societe?.email">E-mail : {{ societe.email }}<br></template>
            <template v-if="societe?.web">Web : {{ societe.web }}</template>
          </div>
        </td>
        <td style="width:42%;border-bottom:none;vertical-align:middle;">
          <div class="inv-title">Facture</div>
          <div class="inv-title-en">{{ form.numero || 'Brouillon' }}</div>
        </td>
      </tr>
    </table>

    <table class="inv">
      <thead>
        <tr class="inv-head">
          <th style="width:50%;">Émetteur</th>
          <th style="width:50%;">Client</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="inv-party">
            <strong>{{ societeNom }}</strong><br>
            {{ societe?.adresse || '—' }}<br>
            <template v-if="societe?.telephone">Tél. : {{ societe.telephone }}<br></template>
            <template v-if="societe?.email">E-mail : {{ societe.email }}</template>
          </td>
          <td class="inv-party">
            <strong>{{ clientFacture.nomination || clientFacture.nom || '—' }}</strong><br>
            {{ clientAdresse }}<br>
            <template v-if="clientFacture.ice_cin">ICE : {{ clientFacture.ice_cin }}<br></template>
            <template v-if="clientFacture.numero_tva">N° TVA : {{ clientFacture.numero_tva }}<br></template>
            <template v-if="clientFacture.telephone">Tél. : {{ clientFacture.telephone }}</template>
          </td>
        </tr>
      </tbody>
    </table>

    <table class="inv" style="border-top:none;">
      <tr>
        <td style="width:25%;">
          <div class="inv-label">N° facture</div>
          <div class="inv-val">{{ form.numero || '—' }}</div>
        </td>
        <td style="width:25%;">
          <div class="inv-label">Date</div>
          <div class="inv-val">{{ formatDateFr(form.date_facture) }}</div>
        </td>
        <td style="width:25%;">
          <div class="inv-label">Échéance</div>
          <div class="inv-val">{{ formatDateFr(form.echeance) }}</div>
        </td>
        <td style="width:25%;">
          <div class="inv-label">Devise</div>
          <div class="inv-val">{{ form.devise || 'MAD' }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="inv-label">Réf. client</div>
          <div class="inv-val">{{ form.reference_client || '—' }}</div>
        </td>
        <td>
          <div class="inv-label">N° commande</div>
          <div class="inv-val">{{ form.numero_commande || '—' }}</div>
        </td>
        <td>
          <div class="inv-label">N° BL</div>
          <div class="inv-val">{{ form.numero_bl || '—' }}</div>
        </td>
        <td>
          <div class="inv-label">Paiement</div>
          <div class="inv-val">{{ form.mode_paiement || form.conditions_paiement || '—' }}</div>
        </td>
      </tr>
    </table>

    <table class="inv" style="margin-top:8px;">
      <thead>
        <tr class="inv-head">
          <th>Réf.</th>
          <th>Désignation</th>
          <th>Calibre</th>
          <th>Emballage</th>
          <th>Lot</th>
          <th class="inv-right">Qté</th>
          <th class="inv-right">PU HT</th>
          <th class="inv-center">TVA</th>
          <th class="inv-right">Montant HT</th>
          <th class="inv-right">Montant TTC</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(ligne, idx) in form.lignes" :key="'pf-'+idx">
          <td>{{ ligne.ref_article || '—' }}</td>
          <td>{{ ligne.designation || '—' }}</td>
          <td>{{ ligne.calibre || '—' }}</td>
          <td>{{ ligne.emballage || '—' }}</td>
          <td>{{ ligne.numero_lot || '—' }}</td>
          <td class="inv-right">{{ formatQty(ligne.quantite) }} {{ ligne.unite || '' }}</td>
          <td class="inv-money">{{ formatMoney(ligne.prix_unitaire) }}</td>
          <td class="inv-center">{{ ligne.tva_taux }} %</td>
          <td class="inv-money">{{ formatMoney(ligne.montant_ht) }}</td>
          <td class="inv-money">{{ formatMoney(ligne.montant_ttc) }}</td>
        </tr>
      </tbody>
    </table>

    <table class="inv" style="margin-top:0;border-top:none;width:48%;margin-left:auto;">
      <tr class="inv-total-row">
        <td>Total HT</td>
        <td class="inv-money" style="width:40%;">{{ formatMoney(totals.ht) }} {{ form.devise }}</td>
      </tr>
      <tr>
        <td>Total TVA</td>
        <td class="inv-money">{{ formatMoney(totals.tva) }} {{ form.devise }}</td>
      </tr>
      <tr class="inv-grand">
        <td>Total TTC</td>
        <td class="inv-money">{{ formatMoney(totals.ttc) }} {{ form.devise }}</td>
      </tr>
    </table>

    <table class="inv" style="margin-top:10px;">
      <thead>
        <tr class="inv-head">
          <th colspan="2">Conditions</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="inv-terms-label">Paiement</td>
          <td>{{ form.conditions_paiement || '—' }}</td>
        </tr>
        <tr>
          <td class="inv-terms-label">Mode</td>
          <td>{{ form.mode_paiement || '—' }}</td>
        </tr>
        <tr v-if="form.observations">
          <td class="inv-terms-label">Observations</td>
          <td>{{ form.observations }}</td>
        </tr>
      </tbody>
    </table>

    <table v-if="societe?.banque || societe?.iban || societe?.rib" class="inv" style="margin-top:10px;">
      <thead>
        <tr class="inv-head">
          <th colspan="4" style="text-align:left;">Coordonnées bancaires</th>
        </tr>
        <tr class="inv-head">
          <th style="width:28%;">Banque</th>
          <th style="width:22%;">Agence</th>
          <th style="width:32%;">N° de compte</th>
          <th style="width:18%;">SWIFT / BIC</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{ societe.banque || '—' }}</td>
          <td>{{ societe.agence || '—' }}</td>
          <td>{{ societe.iban || societe.rib || '—' }}</td>
          <td>{{ societe.swift || '—' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from 'axios';

function emptyLigne() {
  return {
    article_id: null,
    ref_article: '',
    designation: '',
    calibre: '',
    emballage: '',
    numero_lot: '',
    quantite: 0,
    unite: 'kg',
    prix_unitaire: 0,
    tva_taux: 20,
    montant_ht: 0,
    montant_tva: 0,
    montant_ttc: 0,
  };
}

export default {
  name: 'FacturationLocale',
  data() {
    return {
      menu: { item: null, top: 0, left: 0 },
      deleteTarget: null,
      deleting: false,
      deleteError: '',
      items: [],
      clients: [],
      blDispo: [],
      loading: false,
      saving: false,
      search: '',
      statut: '',
      timer: null,
      showModal: false,
      showFromBl: false,
      form: null,
      blForm: { bon_livraison_id: null },
      error: '',
      message: '',
      actionError: '',
      downloadingId: null,
      societe: null,
    };
  },
  computed: {
    societeNom() {
      return this.societe?.nom_societe || this.societe?.nom || '—';
    },
    clientFacture() {
      if (!this.form) return {};
      if (this.form.client) return this.form.client;
      return this.clients.find(c => c.id === this.form.client_id) || {};
    },
    clientAdresse() {
      const c = this.clientFacture;
      const parts = [c.adresse_livraison || c.adresse, c.ville, c.pays].filter(Boolean);
      return parts.join(', ') || '—';
    },
    totals() {
      const lignes = this.form?.lignes || [];
      return {
        ht: lignes.reduce((s, l) => s + (Number(l.montant_ht) || 0), 0),
        tva: lignes.reduce((s, l) => s + (Number(l.montant_tva) || 0), 0),
        ttc: lignes.reduce((s, l) => s + (Number(l.montant_ttc) || 0), 0),
      };
    },
  },
  mounted() {
    this.load();
    this.loadClients();
    this.loadSociete();
    if (this.$route.query.open) {
      this.openById(Number(this.$route.query.open));
    }
  },
  methods: {
    toggleMenu(item, event) {
      if (this.menu.item && this.menu.item.id === item.id) { this.closeMenu(); return; }
      const r = event.currentTarget.getBoundingClientRect();
      const h = 160;
      let top = r.bottom + 4;
      if (top + h > window.innerHeight) top = Math.max(8, r.top - h - 4);
      this.menu = { item, top, left: Math.max(8, r.right - 256) };
    },
    closeMenu() {
      this.menu = { item: null, top: 0, left: 0 };
    },
    runMenu(fn) {
      const it = this.menu.item;
      this.closeMenu();
      if (it) fn(it);
    },
    openDelete(item) {
      this.deleteError = '';
      this.deleteTarget = item;
    },
    async confirmDelete(mode) {
      const item = this.deleteTarget;
      if (!item) return;
      const msg = mode === 'definitif'
        ? `Supprimer DÉFINITIVEMENT ${item.numero} ? La facture, son bon de livraison et la commande d'origine seront supprimés. Cette action est irréversible.`
        : `Supprimer ${item.numero} et remettre le document d'origine ?`;
      if (!confirm(msg)) return;
      this.deleting = true;
      this.deleteError = '';
      try {
        const { data } = await axios.delete(`/api/factures-locales/${item.id}`, { params: { mode } });
        this.deleteTarget = null;
        await this.load();
        if (data?.message) alert(data.message);
      } catch (e) {
        this.deleteError = e.response?.data?.message || 'Erreur lors de la suppression';
      } finally {
        this.deleting = false;
      }
    },
    labelStatut(s) {
      return ({ brouillon: 'Brouillon', validee: 'Validée', payee: 'Payée', annulee: 'Annulée' })[s] || s;
    },
    badgeClass(s) {
      return ({
        brouillon: 'bg-slate-100 text-slate-700',
        validee: 'bg-blue-100 text-blue-700',
        payee: 'bg-green-100 text-green-700',
        annulee: 'bg-red-100 text-red-700',
      })[s] || 'bg-gray-100 text-gray-700';
    },
    formatDate(d) {
      return d ? String(d).slice(0, 10) : '—';
    },
    formatDateFr(d) {
      if (!d) return '—';
      const [y, m, day] = String(d).slice(0, 10).split('-');
      return day && m && y ? `${day}/${m}/${y}` : String(d).slice(0, 10);
    },
    formatQty(v) {
      return Number(v || 0).toLocaleString('fr-FR', { maximumFractionDigits: 3 });
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    recalcLigne(ligne) {
      const qte = Number(ligne.quantite || 0);
      const prix = Number(ligne.prix_unitaire || 0);
      const tva = Number(ligne.tva_taux || 0);
      const ht = Math.round(qte * prix * 100) / 100;
      const mtva = Math.round(ht * (tva / 100) * 100) / 100;
      ligne.montant_ht = ht;
      ligne.montant_tva = mtva;
      ligne.montant_ttc = Math.round((ht + mtva) * 100) / 100;
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/factures-locales', {
          params: { search: this.search, statut: this.statut, per_page: 50 },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async loadClients() {
      const { data } = await axios.get('/api/clients', { params: { per_page: 500, statut: 'client' } });
      this.clients = data.data || data;
    },
    async loadSociete() {
      try {
        const { data } = await axios.get('/api/exportateurs');
        const list = data.data || data || [];
        this.societe = list.find(e => e.est_defaut) || list[0] || null;
      } catch {
        this.societe = null;
      }
    },
    printFacture() {
      if (!this.form?.id) return;
      this.$nextTick(() => window.print());
    },
    async downloadPdf(item) {
      this.downloadingId = item.id;
      this.actionError = '';
      try {
        const filename = `Facture_${item.numero || item.id}.pdf`.replace(/[^\w.\-]+/g, '_');
        const { data } = await axios.get(`/api/factures-locales/${item.id}/pdf`, {
          responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (e) {
        this.actionError = await this.readError(e, 'Impossible d\'exporter la facture');
      } finally {
        this.downloadingId = null;
      }
    },
    async readError(e, fallback) {
      const data = e.response?.data;
      if (data instanceof Blob) {
        try {
          const json = JSON.parse(await data.text());
          return json.message || fallback;
        } catch {
          return fallback;
        }
      }
      return data?.message || fallback;
    },
    emptyForm() {
      return {
        id: null,
        numero: '',
        date_facture: new Date().toISOString().slice(0, 10),
        client_id: null,
        reference_client: '',
        numero_bl: '',
        numero_commande: '',
        devise: 'MAD',
        conditions_paiement: '',
        mode_paiement: '',
        echeance: '',
        statut: 'brouillon',
        observations: '',
        lignes: [emptyLigne()],
      };
    },
    openCreate() {
      this.error = '';
      this.message = '';
      this.form = this.emptyForm();
      this.showModal = true;
    },
    async openFromBl() {
      this.error = '';
      this.blForm.bon_livraison_id = null;
      try {
        const { data } = await axios.get('/api/bons-livraison', { params: { per_page: 200 } });
        const all = data.data || data;
        this.blDispo = all.filter(b => !b.facture_locale);
      } catch {
        this.blDispo = [];
      }
      this.showFromBl = true;
    },
    async createFromBl() {
      if (!this.blForm.bon_livraison_id) return;
      this.saving = true;
      this.error = '';
      try {
        const { data } = await axios.post(`/api/factures-locales/from-bl/${this.blForm.bon_livraison_id}`);
        this.showFromBl = false;
        await this.load();
        this.openDetail(data);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur création facture';
      } finally {
        this.saving = false;
      }
    },
    async openById(id) {
      if (!id) return;
      try {
        const { data } = await axios.get(`/api/factures-locales/${id}`);
        this.openDetail(data);
      } catch {
        /* ignore */
      }
    },
    openDetail(item) {
      this.error = '';
      this.message = '';
      this.form = {
        ...item,
        date_facture: item.date_facture?.slice?.(0, 10) || item.date_facture || '',
        echeance: item.echeance?.slice?.(0, 10) || item.echeance || '',
        lignes: (item.lignes || []).map(l => ({ ...emptyLigne(), ...l })),
      };
      if (!this.form.lignes.length) this.form.lignes = [emptyLigne()];
      this.showModal = true;
    },
    closeDetail() {
      this.showModal = false;
      this.form = null;
    },
    addLigne() {
      this.form.lignes.push(emptyLigne());
    },
    async save() {
      if (!this.form) return;
      this.saving = true;
      this.error = '';
      this.message = '';
      this.form.lignes.forEach(l => this.recalcLigne(l));
      try {
        const payload = {
          date_facture: this.form.date_facture,
          client_id: this.form.client_id,
          reference_client: this.form.reference_client,
          numero_bl: this.form.numero_bl,
          numero_commande: this.form.numero_commande,
          devise: this.form.devise,
          conditions_paiement: this.form.conditions_paiement,
          mode_paiement: this.form.mode_paiement,
          echeance: this.form.echeance || null,
          statut: this.form.statut,
          observations: this.form.observations,
          lignes: this.form.lignes,
        };
        let data;
        if (this.form.id) {
          ({ data } = await axios.put(`/api/factures-locales/${this.form.id}`, payload));
        } else {
          ({ data } = await axios.post('/api/factures-locales', payload));
        }
        this.openDetail(data);
        this.message = 'Facture enregistrée';
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur enregistrement';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style>
.facture-print {
  display: none;
}

@media print {
  body * {
    visibility: hidden;
  }

  .facture-print,
  .facture-print * {
    visibility: visible;
  }

  .facture-print {
    display: block !important;
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    color: #111;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    padding: 8px;
  }

  .facture-print .inv {
    width: 100%;
    border-collapse: collapse;
  }

  .facture-print .inv th,
  .facture-print .inv td {
    border: 1px solid #222;
    padding: 4px 6px;
    vertical-align: top;
    font-size: 9px;
  }

  .facture-print .inv-head th {
    background: #2d5a42;
    color: #fff;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 9px;
  }

  .facture-print .inv-label {
    color: #555;
    font-size: 8px;
    text-transform: uppercase;
  }

  .facture-print .inv-val {
    font-weight: 600;
    font-size: 10px;
  }

  .facture-print .inv-brand {
    font-size: 16px;
    font-weight: 700;
    color: #1a3d2b;
    margin: 0;
  }

  .facture-print .inv-tagline {
    font-size: 8px;
    color: #555;
    margin: 2px 0 6px;
  }

  .facture-print .inv-contact,
  .facture-print .inv-party {
    font-size: 9px;
    line-height: 1.45;
  }

  .facture-print .inv-title {
    font-size: 18px;
    font-weight: 700;
    text-align: right;
    text-transform: uppercase;
    margin: 0;
  }

  .facture-print .inv-title-en {
    font-size: 11px;
    text-align: right;
    color: #555;
    margin: 2px 0 0;
  }

  .facture-print .inv-money {
    text-align: right;
    white-space: nowrap;
  }

  .facture-print .inv-center { text-align: center; }
  .facture-print .inv-right { text-align: right; }

  .facture-print .inv-total-row td {
    font-weight: 700;
    background: #f3f4f6;
  }

  .facture-print .inv-grand td {
    font-weight: 700;
    background: #2d5a42;
    color: #fff;
    font-size: 11px;
  }

  .facture-print .inv-terms-label {
    width: 22%;
    font-weight: 700;
    background: #f0f4f1;
    text-transform: uppercase;
    font-size: 8px;
  }
}
</style>
