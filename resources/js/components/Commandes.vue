<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Commandes</h1>
          <p class="text-gray-600 text-sm mt-1">
            Brouillon → Confirmée → Préparation → Livraison → Clôture
          </p>
        </div>
        <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouvelle commande
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 overflow-x-auto">
        <div class="flex items-center gap-1 min-w-max">
          <template v-for="(step, i) in processusSteps" :key="step.key">
            <button
              type="button"
              @click="statut = statut === step.key ? '' : step.key; load()"
              class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
              :class="statut === step.key ? 'bg-teal-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-teal-50'"
            >{{ step.label }}</button>
            <span v-if="i < processusSteps.length - 1" class="text-gray-300 px-1">→</span>
          </template>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher N°, client, commercial, port de chargement…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="type" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous les types</option>
          <option value="local">Local</option>
          <option value="export">Export</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N°</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Type</th>
              <th class="py-3 px-4">Priorité</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4">Date souhaitée</th>
              <th class="py-3 px-4">Total TTC</th>
              <th class="py-3 px-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucune commande</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_commande) }}</td>
              <td class="py-3 px-4">{{ item.client?.nom || '—' }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded text-xs font-medium" :class="item.type === 'export' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700'">
                  {{ item.type === 'export' ? 'Export' : 'Local' }}
                </span>
              </td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded text-xs font-medium" :class="prioriteClass(item.priorite)">{{ labelPriorite(item.priorite) }}</span>
              </td>
              <td class="py-3 px-4">
                <span :class="badgeClass(item.statut)" class="px-2 py-1 rounded text-xs font-medium">{{ labelStatut(item.statut) }}</span>
              </td>
              <td class="py-3 px-4">{{ formatDate(item.date_souhaitee) }}</td>
              <td class="py-3 px-4 font-medium">{{ formatMoney(item.total_ttc) }} {{ item.devise }}</td>
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1">
                  <button
                    @click="openAddLignes(item)"
                    type="button"
                    title="Ajouter lignes"
                    class="p-2 rounded-lg text-amber-600 hover:bg-amber-50 transition-colors"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                  <button
                    @click="transformerEnBl(item)"
                    type="button"
                    title="Transformer en BL"
                    :disabled="transformingId === item.id || convertingColisageId === item.id"
                    class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors disabled:opacity-50"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </button>
                  <button
                    @click="transformerEnColisage(item)"
                    type="button"
                    title="Transformer en liste de colisage"
                    :disabled="transformingId === item.id || convertingColisageId === item.id"
                    class="p-2 rounded-lg text-violet-600 hover:bg-violet-50 transition-colors disabled:opacity-50"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                  </button>
                  <router-link
                    :to="`/ventes/commandes/${item.id}`"
                    title="Ouvrir"
                    class="p-2 rounded-lg text-teal-600 hover:bg-teal-50 transition-colors inline-flex"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Create modal -->
      <div v-if="showModal" class="app-modal-overlay" @click.self="showModal=false">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">Nouvelle commande</h2>
            <button type="button" class="app-modal__close" @click="showModal=false" aria-label="Fermer">&times;</button>
          </div>

          <div class="app-modal__body">
            <div class="achat-header-grid mb-5">
              <div>
                <label class="text-sm text-gray-600">Date</label>
                <input type="date" v-model="form.date_commande" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Client</label>
                <select v-model="form.client_id" @change="onClientChange" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Référence client</label>
                <input v-model="form.reference_client" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Commercial</label>
                <input v-model="form.commercial" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Devise</label>
                <input :value="form.devise" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-600" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Paiement</label>
                <input :value="form.mode_paiement || '—'" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-600" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Date souhaitée</label>
                <input type="date" v-model="form.date_souhaitee" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Priorité</label>
                <select v-model="form.priorite" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option value="basse">Basse</option>
                  <option value="normale">Normale</option>
                  <option value="haute">Haute</option>
                  <option value="urgente">Urgente</option>
                </select>
              </div>
              <div class="achat-span-full">
                <label class="text-sm text-gray-600">Adresse</label>
                <textarea v-model="form.adresse" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
              <div class="achat-span-full">
                <label class="text-sm text-gray-600">Observation</label>
                <textarea v-model="form.observations" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div class="mb-5">
              <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900">Ajouter une ligne</h3>
                <button @click="commitLigne" type="button" class="text-sm font-medium px-3 py-1.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700">+ Ajouter à la liste</button>
              </div>

              <div class="app-line-card">
                <div class="flex flex-col sm:flex-row gap-3 mb-3">
                  <div class="w-full sm:w-28 shrink-0">
                    <label class="text-xs text-gray-500">Article</label>
                    <select v-model="ligneDraft.article_id" @change="fillArticle(ligneDraft)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                      <option :value="null">—</option>
                      <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }}</option>
                    </select>
                  </div>
                  <div class="flex-1 min-w-0">
                    <label class="text-xs text-gray-500">Désignation</label>
                    <input v-model="ligneDraft.designation" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                  </div>
                </div>

                <div class="achat-grid-4 mb-3">
                  <div>
                    <label class="text-xs text-gray-500">Calibre</label>
                    <input v-model="ligneDraft.calibre" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Type emballage primaire</label>
                    <input v-model="ligneDraft.type_emballage_primaire" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Référence d'emballage</label>
                    <input v-model="ligneDraft.reference_emballage" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Type emballage secondaire</label>
                    <input v-model="ligneDraft.type_emballage_secondaire" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                  </div>
                </div>

                <div class="achat-grid-4 mb-3">
                  <div>
                    <label class="text-xs text-gray-500">Unités par colis</label>
                    <input
                      type="number"
                      min="0"
                      step="1"
                      v-model.number="ligneDraft.unites_par_colis"
                      @input="onPackagingChange(ligneDraft)"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                    />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Colis par palette</label>
                    <input
                      type="number"
                      min="0"
                      step="1"
                      v-model.number="ligneDraft.colis_par_palette"
                      @input="onPackagingChange(ligneDraft)"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                    />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Nombre total par palette</label>
                    <input v-model.number="ligneDraft.nombre_total_par_palette" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Poids net égoutté</label>
                    <input v-model.number="ligneDraft.poids_net_egoutte" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                  </div>
                </div>

                <div class="achat-grid-4 mb-3">
                  <div>
                    <label class="text-xs text-gray-500">Qté</label>
                    <input
                      type="number"
                      step="0.001"
                      v-model.number="ligneDraft.quantite"
                      readonly
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 font-medium"
                    />
                    <p class="text-[10px] text-gray-400 mt-0.5">= Nombre total × Poids net égoutté</p>
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Unité</label>
                    <input v-model="ligneDraft.unite" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Prix</label>
                    <input type="number" step="0.01" v-model.number="ligneDraft.prix" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">TVA %</label>
                    <input type="number" step="0.01" v-model.number="ligneDraft.tva_taux" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                  </div>
                </div>

                <div class="achat-grid-4">
                  <div>
                    <label class="text-xs text-gray-500">Date de production</label>
                    <input type="date" v-model="ligneDraft.date_production" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">N° de lot</label>
                    <input v-model="ligneDraft.lot" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">HT</label>
                    <div class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 font-medium">{{ formatMoney(ligneHt(ligneDraft)) }}</div>
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Disponible</label>
                    <div class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50">{{ ligneDraft.quantite_disponible || 0 }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <h3 class="font-semibold text-gray-900 mb-3">Lignes ajoutées ({{ form.lignes.length }})</h3>
              <div class="overflow-x-auto border border-gray-100 rounded-lg">
                <table class="w-full text-sm min-w-max">
                  <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                      <th class="py-2 px-3 whitespace-nowrap">Article</th>
                      <th class="py-2 px-3 whitespace-nowrap">Désignation</th>
                      <th class="py-2 px-3 whitespace-nowrap">Calibre</th>
                      <th class="py-2 px-3 whitespace-nowrap">Emb. primaire</th>
                      <th class="py-2 px-3 whitespace-nowrap">Réf. emballage</th>
                      <th class="py-2 px-3 whitespace-nowrap">Emb. secondaire</th>
                      <th class="py-2 px-3 whitespace-nowrap">Unités/colis</th>
                      <th class="py-2 px-3 whitespace-nowrap">Colis/palette</th>
                      <th class="py-2 px-3 whitespace-nowrap">Total/palette</th>
                      <th class="py-2 px-3 whitespace-nowrap">Poids net ég.</th>
                      <th class="py-2 px-3 whitespace-nowrap">Qté</th>
                      <th class="py-2 px-3 whitespace-nowrap">Unité</th>
                      <th class="py-2 px-3 whitespace-nowrap">Prix</th>
                      <th class="py-2 px-3 whitespace-nowrap">Date prod.</th>
                      <th class="py-2 px-3 whitespace-nowrap">N° lot</th>
                      <th class="py-2 px-3 whitespace-nowrap">TVA %</th>
                      <th class="py-2 px-3 whitespace-nowrap">HT</th>
                      <th class="py-2 px-3 whitespace-nowrap">Dispo</th>
                      <th class="py-2 px-3"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!form.lignes.length">
                      <td colspan="19" class="py-6 text-center text-gray-400">Aucune ligne ajoutée</td>
                    </tr>
                    <tr v-for="(ligne, idx) in form.lignes" :key="idx" class="border-t border-gray-50">
                      <td class="py-2 px-3 whitespace-nowrap">{{ articleLabel(ligne.article_id) }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.designation || '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.calibre || '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.type_emballage_primaire || '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.reference_emballage || '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.type_emballage_secondaire || '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.unites_par_colis ?? '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.colis_par_palette ?? '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.nombre_total_par_palette ?? '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.poids_net_egoutte ?? '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.quantite }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.unite || '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ formatMoney(ligne.prix) }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.date_production || '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.lot || '—' }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.tva_taux ?? 20 }}</td>
                      <td class="py-2 px-3 whitespace-nowrap font-medium">{{ formatMoney(ligneHt(ligne)) }}</td>
                      <td class="py-2 px-3 whitespace-nowrap">{{ ligne.quantite_disponible || 0 }}</td>
                      <td class="py-2 px-3 text-right">
                        <button type="button" @click="form.lignes.splice(idx,1)" class="text-red-500 text-xs">Supprimer</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="app-modal__footer app-modal__footer--between">
            <div class="flex flex-wrap gap-4 text-sm text-gray-700 achat-md-row">
              <span>HT : <strong>{{ formatMoney(totalHt) }}</strong></span>
              <span>TVA : <strong>{{ formatMoney(totalTva) }}</strong></span>
              <span>TTC : <strong class="text-teal-700">{{ formatMoney(totalTtc) }} {{ form.devise }}</strong></span>
            </div>
            <div class="flex justify-end gap-3">
              <button @click="showModal=false" type="button" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
              <button @click="save" type="button" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-lg">{{ saving ? 'Enregistrement…' : 'Créer' }}</button>
            </div>
            <p v-if="error" class="text-red-600 text-sm w-full">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Add lignes modal -->
      <div v-if="showAddLignesModal" class="app-modal-overlay" @click.self="showAddLignesModal=false">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">Ajouter des lignes — {{ addLignesCommande?.numero }}</h2>
            <button type="button" class="app-modal__close" @click="showAddLignesModal=false">&times;</button>
          </div>
          <div class="app-modal__body">
            <div class="mb-4 overflow-x-auto border border-gray-100 rounded-lg">
              <table class="w-full text-sm min-w-max">
                <thead class="bg-gray-50 text-left text-gray-500">
                  <tr>
                    <th class="py-2 px-3 whitespace-nowrap">Article</th>
                    <th class="py-2 px-3 whitespace-nowrap">Désignation</th>
                    <th class="py-2 px-3 whitespace-nowrap">Calibre</th>
                    <th class="py-2 px-3 whitespace-nowrap">Emb. primaire</th>
                    <th class="py-2 px-3 whitespace-nowrap">Réf. emballage</th>
                    <th class="py-2 px-3 whitespace-nowrap">Emb. secondaire</th>
                    <th class="py-2 px-3 whitespace-nowrap">Unités/colis</th>
                    <th class="py-2 px-3 whitespace-nowrap">Colis/palette</th>
                    <th class="py-2 px-3 whitespace-nowrap">Total/palette</th>
                    <th class="py-2 px-3 whitespace-nowrap">Poids net ég.</th>
                    <th class="py-2 px-3 whitespace-nowrap">Qté</th>
                    <th class="py-2 px-3 whitespace-nowrap">Unité</th>
                    <th class="py-2 px-3 whitespace-nowrap">Prix</th>
                    <th class="py-2 px-3 whitespace-nowrap">Date prod.</th>
                    <th class="py-2 px-3 whitespace-nowrap">N° lot</th>
                    <th class="py-2 px-3 whitespace-nowrap">HT</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(ligne, idx) in existingLignes" :key="'ex-'+idx" class="border-t border-gray-50">
                    <td class="py-2 px-3 whitespace-nowrap">{{ articleLabel(ligne.article_id) }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.designation || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.calibre || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.type_emballage_primaire || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.reference_emballage || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.type_emballage_secondaire || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.unites_par_colis ?? '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.colis_par_palette ?? '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.nombre_total_par_palette ?? '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.poids_net_egoutte ?? '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.quantite }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.unite || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ formatMoney(ligne.prix) }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.date_production || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.lot || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ formatMoney(ligneHt(ligne)) }}</td>
                  </tr>
                  <tr v-if="!existingLignes.length">
                    <td colspan="16" class="py-4 text-center text-gray-400">Aucune ligne existante</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="app-line-card mb-4">
              <div class="flex flex-col sm:flex-row gap-3 mb-3">
                <div class="w-full sm:w-28 shrink-0">
                  <label class="text-xs text-gray-500">Article</label>
                  <select v-model="ligneDraft.article_id" @change="fillArticle(ligneDraft)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option :value="null">—</option>
                    <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }}</option>
                  </select>
                </div>
                <div class="flex-1 min-w-0">
                  <label class="text-xs text-gray-500">Désignation</label>
                  <input v-model="ligneDraft.designation" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
              </div>
              <div class="achat-grid-4 mb-3">
                <div>
                  <label class="text-xs text-gray-500">Calibre</label>
                  <input v-model="ligneDraft.calibre" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                </div>
                <div>
                  <label class="text-xs text-gray-500">Type emballage primaire</label>
                  <input v-model="ligneDraft.type_emballage_primaire" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                </div>
                <div>
                  <label class="text-xs text-gray-500">Référence d'emballage</label>
                  <input v-model="ligneDraft.reference_emballage" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                </div>
                <div>
                  <label class="text-xs text-gray-500">Type emballage secondaire</label>
                  <input v-model="ligneDraft.type_emballage_secondaire" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                </div>
              </div>
              <div class="achat-grid-4 mb-3">
                <div>
                  <label class="text-xs text-gray-500">Unités / colis</label>
                  <input
                    type="number"
                    min="0"
                    step="1"
                    v-model.number="ligneDraft.unites_par_colis"
                    @input="onPackagingChange(ligneDraft)"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label class="text-xs text-gray-500">Colis / palette</label>
                  <input
                    type="number"
                    min="0"
                    step="1"
                    v-model.number="ligneDraft.colis_par_palette"
                    @input="onPackagingChange(ligneDraft)"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label class="text-xs text-gray-500">Total / palette</label>
                  <input v-model.number="ligneDraft.nombre_total_par_palette" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                </div>
                <div>
                  <label class="text-xs text-gray-500">Poids net égoutté</label>
                  <input v-model.number="ligneDraft.poids_net_egoutte" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50" />
                </div>
              </div>
              <div class="achat-grid-4 mb-3">
                <div>
                  <label class="text-xs text-gray-500">Qté</label>
                  <input type="number" step="0.001" v-model.number="ligneDraft.quantite" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 font-medium" />
                  <p class="text-[10px] text-gray-400 mt-0.5">= Nombre total × Poids net égoutté</p>
                </div>
                <div>
                  <label class="text-xs text-gray-500">Unité</label>
                  <input v-model="ligneDraft.unite" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                  <label class="text-xs text-gray-500">Prix</label>
                  <input type="number" step="0.01" v-model.number="ligneDraft.prix" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                  <label class="text-xs text-gray-500">TVA %</label>
                  <input type="number" step="0.01" v-model.number="ligneDraft.tva_taux" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
              </div>
              <div class="achat-grid-4 mb-3">
                <div>
                  <label class="text-xs text-gray-500">Date de production</label>
                  <input type="date" v-model="ligneDraft.date_production" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                  <label class="text-xs text-gray-500">N° de lot</label>
                  <input v-model="ligneDraft.lot" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
              </div>
              <div class="flex justify-end">
                <button type="button" @click="commitAddLigne" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm">+ Ajouter à la liste</button>
              </div>
            </div>

            <div v-if="newLignes.length" class="overflow-x-auto border border-gray-100 rounded-lg">
              <p class="text-sm font-medium text-gray-700 px-3 pt-3">Nouvelles lignes ({{ newLignes.length }})</p>
              <table class="w-full text-sm min-w-max">
                <thead class="bg-gray-50 text-left text-gray-500">
                  <tr>
                    <th class="py-2 px-3 whitespace-nowrap">Article</th>
                    <th class="py-2 px-3 whitespace-nowrap">Désignation</th>
                    <th class="py-2 px-3 whitespace-nowrap">Calibre</th>
                    <th class="py-2 px-3 whitespace-nowrap">Emb. primaire</th>
                    <th class="py-2 px-3 whitespace-nowrap">Réf. emballage</th>
                    <th class="py-2 px-3 whitespace-nowrap">Emb. secondaire</th>
                    <th class="py-2 px-3 whitespace-nowrap">Unités/colis</th>
                    <th class="py-2 px-3 whitespace-nowrap">Colis/palette</th>
                    <th class="py-2 px-3 whitespace-nowrap">Total/palette</th>
                    <th class="py-2 px-3 whitespace-nowrap">Poids net ég.</th>
                    <th class="py-2 px-3 whitespace-nowrap">Qté</th>
                    <th class="py-2 px-3 whitespace-nowrap">Unité</th>
                    <th class="py-2 px-3 whitespace-nowrap">Prix</th>
                    <th class="py-2 px-3 whitespace-nowrap">Date prod.</th>
                    <th class="py-2 px-3 whitespace-nowrap">N° lot</th>
                    <th class="py-2 px-3 whitespace-nowrap">HT</th>
                    <th class="py-2 px-3"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(ligne, idx) in newLignes" :key="'new-'+idx" class="border-t border-gray-50">
                    <td class="py-2 px-3 whitespace-nowrap">{{ articleLabel(ligne.article_id) }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.designation || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.calibre || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.type_emballage_primaire || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.reference_emballage || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.type_emballage_secondaire || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.unites_par_colis ?? '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.colis_par_palette ?? '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.nombre_total_par_palette ?? '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.poids_net_egoutte ?? '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.quantite }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.unite || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ formatMoney(ligne.prix) }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.date_production || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.lot || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ formatMoney(ligneHt(ligne)) }}</td>
                    <td class="py-2 px-3 text-right">
                      <button type="button" @click="newLignes.splice(idx,1)" class="text-red-500 text-xs">Supprimer</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="app-modal__footer">
            <p v-if="error" class="text-red-600 text-sm mr-auto">{{ error }}</p>
            <button @click="showAddLignesModal=false" type="button" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
            <button @click="saveAddLignes" type="button" :disabled="saving || !newLignes.length" class="px-5 py-2 bg-teal-600 text-white rounded-lg disabled:opacity-50">
              {{ saving ? 'Enregistrement…' : 'Enregistrer les lignes' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

const PROCESSUS = [
  { key: 'brouillon', label: 'Brouillon' },
  { key: 'en_attente', label: 'En attente' },
  { key: 'confirmee', label: 'Confirmée' },
  { key: 'en_preparation', label: 'En préparation' },
  { key: 'en_production', label: 'En production' },
  { key: 'partiellement_livree', label: 'Partiellement livrée' },
  { key: 'livree', label: 'Livrée' },
  { key: 'cloturee', label: 'Clôturée' },
];

export default {
  name: 'Commandes',
  data() {
    return {
      items: [],
      clients: [],
      articles: [],
      stockMap: {},
      loading: false,
      saving: false,
      transformingId: null,
      convertingColisageId: null,
      showModal: false,
      showAddLignesModal: false,
      addLignesCommande: null,
      existingLignes: [],
      newLignes: [],
      search: '',
      statut: '',
      type: '',
      error: '',
      timer: null,
      processusSteps: PROCESSUS,
      incoterms: ['EXW', 'FCA', 'FOB', 'CIF', 'CFR', 'DAP', 'DDP', 'CPT', 'CIP'],
      form: this.emptyForm(),
      ligneDraft: this.emptyLigne(),
    };
  },
  computed: {
    totalHt() {
      return this.form.lignes.reduce((s, l) => s + this.ligneHt(l), 0);
    },
    totalTva() {
      return this.form.lignes.reduce((s, l) => s + this.ligneTva(l), 0);
    },
    totalTtc() {
      return this.totalHt + this.totalTva;
    },
  },
  mounted() {
    this.load();
    this.loadLookups();
  },
  methods: {
    emptyForm() {
      return {
        date_commande: new Date().toISOString().slice(0, 10),
        client_id: null,
        reference_client: '',
        commercial: '',
        type: 'local',
        devise: '',
        mode_paiement: '',
        incoterm: '',
        destination: '',
        adresse: '',
        date_souhaitee: '',
        priorite: 'normale',
        observations: '',
        statut: 'brouillon',
        lignes: [],
      };
    },
    emptyLigne() {
      return {
        article_id: null,
        designation: '',
        calibre: '',
        type_emballage_primaire: '',
        reference_emballage: '',
        type_emballage_secondaire: '',
        unites_par_colis: null,
        colis_par_palette: null,
        nombre_total_par_palette: null,
        poids_net_egoutte: null,
        quantite: 0,
        unite: 'kg',
        prix: 0,
        remise: 0,
        tva_taux: 20,
        date_production: '',
        lot: '',
        cout_unitaire: 0,
        quantite_disponible: 0,
        quantite_reservee: 0,
        quantite_a_produire: 0,
      };
    },
    articleLabel(id) {
      const a = this.articles.find(x => x.id === id);
      return a ? a.code_article : '—';
    },
    ligneHt(l) {
      const brut = (Number(l.quantite) || 0) * (Number(l.prix) || 0);
      return Math.round(brut * (1 - (Number(l.remise) || 0) / 100) * 100) / 100;
    },
    ligneTva(l) {
      return Math.round(this.ligneHt(l) * ((Number(l.tva_taux) || 0) / 100) * 100) / 100;
    },
    labelStatut(s) {
      return PROCESSUS.find(p => p.key === s)?.label || s;
    },
    labelPriorite(p) {
      return ({ basse: 'Basse', normale: 'Normale', haute: 'Haute', urgente: 'Urgente' })[p] || p;
    },
    prioriteClass(p) {
      return ({
        basse: 'bg-slate-100 text-slate-600',
        normale: 'bg-gray-100 text-gray-700',
        haute: 'bg-amber-100 text-amber-700',
        urgente: 'bg-red-100 text-red-700',
      })[p] || 'bg-gray-100 text-gray-700';
    },
    badgeClass(s) {
      const map = {
        brouillon: 'bg-slate-100 text-slate-700',
        en_attente: 'bg-amber-100 text-amber-700',
        confirmee: 'bg-blue-100 text-blue-700',
        en_preparation: 'bg-cyan-100 text-cyan-700',
        en_production: 'bg-indigo-100 text-indigo-700',
        partiellement_livree: 'bg-violet-100 text-violet-700',
        livree: 'bg-green-100 text-green-700',
        cloturee: 'bg-emerald-100 text-emerald-800',
      };
      return map[s] || 'bg-gray-100 text-gray-700';
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(d) {
      if (!d) return '—';
      return String(d).slice(0, 10);
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/commandes', {
          params: { search: this.search, statut: this.statut, type: this.type },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async loadLookups() {
      const [c, a, stock] = await Promise.all([
        axios.get('/api/clients', { params: { per_page: 500 } }),
        axios.get('/api/articles', { params: { per_page: 500 } }),
        axios.get('/api/stock/situation', { params: { per_page: 1000 } }).catch(() => ({ data: [] })),
      ]);
      this.clients = (c.data.data || c.data).filter(x => x.type !== 'prospect' && x.actif !== false);
      this.articles = a.data.data || a.data;
      const rows = stock.data.data || stock.data || [];
      const map = {};
      rows.forEach((r) => {
        const id = r.article_id || r.article?.id;
        if (!id) return;
        const dispo = Number(r.stock_disponible ?? r.disponible ?? ((r.stock_theorique || 0) - (r.stock_reserve || 0)));
        map[id] = (map[id] || 0) + dispo;
      });
      this.stockMap = map;
    },
    clientType(client) {
      const cat = String(client?.categorie || '').toLowerCase();
      return cat === 'local' ? 'local' : 'export';
    },
    onClientChange() {
      const client = this.clients.find(c => c.id === this.form.client_id);
      if (!client) return;
      this.form.type = this.clientType(client);
      this.form.devise = client.devise || this.form.devise || '';
      this.form.mode_paiement = client.delai_paiement_type || client.mode_paiement || '';
      this.form.incoterm = client.incoterm || this.form.incoterm || '';
      this.form.destination = client.port_chargement || '';
      if (!this.form.adresse && (client.adresse_livraison || client.adresse || client.ville)) {
        this.form.adresse = client.adresse_livraison
          || [client.adresse, client.ville, client.pays].filter(Boolean).join(', ');
      }
      if (!this.form.commercial && client.commercial_charge) {
        this.form.commercial = client.commercial_charge;
      }
    },
    openCreate() {
      this.form = this.emptyForm();
      this.ligneDraft = this.emptyLigne();
      this.error = '';
      this.showModal = true;
    },
    commitLigne() {
      if (!this.ligneDraft.article_id && !this.ligneDraft.designation) {
        this.error = 'Sélectionnez un article ou saisissez une désignation';
        return;
      }
      this.error = '';
      this.form.lignes.push({ ...this.ligneDraft });
      this.ligneDraft = this.emptyLigne();
    },
    fillArticle(ligne) {
      const a = this.articles.find(x => x.id === ligne.article_id);
      if (!a) return;
      ligne.designation = a.designation || '';
      ligne.calibre = a.calibre || '';
      ligne.type_emballage_primaire = a.type_emballage_primaire || '';
      ligne.reference_emballage = a.type_palette || '';
      ligne.type_emballage_secondaire = a.type_emballage_secondaire || '';
      ligne.unites_par_colis = a.unites_par_colis ?? null;
      ligne.colis_par_palette = a.colis_par_palette ?? null;
      ligne.poids_net_egoutte = a.poids_net_egoutte ?? null;
      ligne.date_production = a.date_production ? String(a.date_production).slice(0, 10) : (ligne.date_production || '');
      ligne.lot = a.lot || ligne.lot || '';
      ligne.unite = a.unite_facturation || a.unite || ligne.unite || 'kg';
      ligne.prix = Number(a.prix_vente || 0);
      ligne.cout_unitaire = Number(a.prix_achat || a.prix_achat_matiere || 0);
      ligne.tva_taux = Number(a.taux_tva ?? ligne.tva_taux ?? 20);
      ligne.quantite_disponible = this.stockMap[a.id] || 0;
      this.recalcNombreEtQuantite(ligne);
    },
    onPackagingChange(ligne) {
      this.recalcNombreEtQuantite(ligne);
    },
    recalcNombreEtQuantite(ligne) {
      const unites = Number(ligne.unites_par_colis) || 0;
      const colis = Number(ligne.colis_par_palette) || 0;
      ligne.nombre_total_par_palette = unites * colis;
      const nombreTotal = Number(ligne.nombre_total_par_palette) || 0;
      const poidsNetEgoutte = Number(ligne.poids_net_egoutte) || 0;
      // Quantité = Nombre total × Poids net égoutté (ex: 72 × 8 = 576)
      ligne.quantite = Math.round(nombreTotal * poidsNetEgoutte * 1000) / 1000;
      this.recalcDispo(ligne);
    },
    recalcDispo(ligne) {
      const qte = Number(ligne.quantite) || 0;
      const dispo = Number(ligne.quantite_disponible) || 0;
      ligne.quantite_reservee = Math.min(dispo, qte);
      ligne.quantite_a_produire = Math.max(0, Math.round((qte - ligne.quantite_reservee) * 1000) / 1000);
    },
    async save() {
      if (!this.form.lignes.length) {
        this.error = 'Ajoutez au moins une ligne à la commande';
        return;
      }
      this.saving = true;
      this.error = '';
      try {
        const { data } = await axios.post('/api/commandes', this.form);
        this.showModal = false;
        this.$router.push(`/ventes/commandes/${data.id}`);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de la création';
      } finally {
        this.saving = false;
      }
    },
    async transformerEnBl(item) {
      if (!confirm(`Transformer la commande ${item.numero} en bon de livraison ? Elle disparaîtra de cette liste.`)) {
        return;
      }
      this.transformingId = item.id;
      this.error = '';
      try {
        await axios.post(`/api/bons-livraison/from-commande/${item.id}`, {
          date_heure_livraison: item.date_souhaitee ? `${String(item.date_souhaitee).slice(0, 10)}T00:00` : null,
          informations_additionnelles: item.observations || null,
        });
        await this.load();
        this.$router.push('/ventes/locales/bons-livraison');
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur transformation en BL';
        alert(this.error);
      } finally {
        this.transformingId = null;
      }
    },
    async transformerEnColisage(item) {
      if (!confirm(`Créer une liste de colisage à partir de la commande ${item.numero} ?`)) {
        return;
      }
      this.convertingColisageId = item.id;
      this.error = '';
      try {
        const { data } = await axios.post(`/api/exportations/from-commande/${item.id}`);
        await this.load();
        this.$router.push(`/exportations/${data.id}`);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur création liste de colisage';
        alert(this.error);
      } finally {
        this.convertingColisageId = null;
      }
    },
    async openAddLignes(item) {
      this.error = '';
      this.newLignes = [];
      this.ligneDraft = this.emptyLigne();
      this.addLignesCommande = item;
      try {
        const { data } = await axios.get(`/api/commandes/${item.id}`);
        this.addLignesCommande = data;
        this.existingLignes = (data.lignes || []).map(l => ({
          article_id: l.article_id,
          designation: l.designation || '',
          calibre: l.calibre || '',
          type_emballage_primaire: l.type_emballage_primaire || '',
          reference_emballage: l.reference_emballage || '',
          type_emballage_secondaire: l.type_emballage_secondaire || '',
          unites_par_colis: l.unites_par_colis,
          colis_par_palette: l.colis_par_palette,
          nombre_total_par_palette: l.nombre_total_par_palette,
          poids_net_egoutte: l.poids_net_egoutte,
          quantite: Number(l.quantite),
          unite: l.unite || '',
          prix: Number(l.prix),
          remise: Number(l.remise || 0),
          tva_taux: Number(l.tva_taux ?? 20),
          date_production: l.date_production ? String(l.date_production).slice(0, 10) : '',
          lot: l.lot || '',
          cout_unitaire: Number(l.cout_unitaire || 0),
          quantite_disponible: Number(l.quantite_disponible || 0),
          quantite_reservee: Number(l.quantite_reservee || 0),
          quantite_a_produire: Number(l.quantite_a_produire || 0),
          quantite_livree: Number(l.quantite_livree || 0),
        }));
        this.showAddLignesModal = true;
      } catch (e) {
        this.error = e.response?.data?.message || 'Impossible de charger la commande';
      }
    },
    commitAddLigne() {
      if (!this.ligneDraft.article_id && !this.ligneDraft.designation) {
        this.error = 'Sélectionnez un article ou saisissez une désignation';
        return;
      }
      this.error = '';
      this.newLignes.push({ ...this.ligneDraft });
      this.ligneDraft = this.emptyLigne();
    },
    async saveAddLignes() {
      if (!this.addLignesCommande || !this.newLignes.length) return;
      this.saving = true;
      this.error = '';
      try {
        await axios.put(`/api/commandes/${this.addLignesCommande.id}`, {
          lignes: [...this.existingLignes, ...this.newLignes],
        });
        this.showAddLignesModal = false;
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de l\'ajout des lignes';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
