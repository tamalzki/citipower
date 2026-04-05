(function () {
  const DB_NAME = 'citipower-offline-db';
  const DB_VERSION = 5;
  const MUTATIONS_STORE = 'pending_mutations';
  const LEGACY_STORES = [
    ['pending_sales', 'sale_create'],
    ['pending_expenses', 'expense_create'],
    ['pending_expense_updates', 'expense_update'],
    ['pending_expense_deletes', 'expense_delete'],
    ['pending_purchase_orders', 'po_create'],
    ['pending_purchase_order_updates', 'po_update'],
    ['pending_purchase_order_deletes', 'po_delete'],
    ['pending_stock_transfers', 'stock_transfer_create'],
    ['pending_sale_deletes', 'sale_void'],
  ];
  const RETRY_MS = 15000;
  let syncInProgress = false;

  function openDb() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = function () {
        const db = req.result;
        LEGACY_STORES.forEach(function (pair) {
          const name = pair[0];
          if (!db.objectStoreNames.contains(name)) {
            db.createObjectStore(name, { keyPath: 'local_id' });
          }
        });
        if (!db.objectStoreNames.contains(MUTATIONS_STORE)) {
          db.createObjectStore(MUTATIONS_STORE, { keyPath: 'local_id' });
        }
      };
      req.onsuccess = function () { resolve(req.result); };
      req.onerror = function () { reject(req.error); };
    });
  }

  function tx(storeNames, mode, work) {
    const names = Array.isArray(storeNames) ? storeNames : [storeNames];
    return openDb().then(function (db) {
      return new Promise(function (resolve, reject) {
        const transaction = db.transaction(names, mode);
        let result;
        try {
          result = work(transaction);
        } catch (err) {
          reject(err);
          return;
        }
        transaction.oncomplete = function () { resolve(result); };
        transaction.onerror = function () { reject(transaction.error); };
        transaction.onabort = function () { reject(transaction.error); };
      });
    });
  }

  function uuid() {
    if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
    return 'local-' + Date.now() + '-' + Math.random().toString(16).slice(2);
  }

  function readAllFromStore(storeName) {
    return tx(storeName, 'readonly', function (transaction) {
      const store = transaction.objectStore(storeName);
      return new Promise(function (resolve, reject) {
        const r = store.getAll();
        r.onsuccess = function () { resolve(r.result || []); };
        r.onerror = function () { reject(r.error); };
      });
    });
  }

  async function migrateLegacyToMutations() {
    const db = await openDb();
    const toWrite = [];
    for (let i = 0; i < LEGACY_STORES.length; i++) {
      const storeName = LEGACY_STORES[i][0];
      const kind = LEGACY_STORES[i][1];
      if (!db.objectStoreNames.contains(storeName)) continue;
      const rows = await readAllFromStore(storeName);
      for (let j = 0; j < rows.length; j++) {
        const row = rows[j];
        toWrite.push({
          storeName: storeName,
          local_id: row.local_id,
          kind: kind,
          payload: row.payload,
          client_timestamp: row.client_timestamp || new Date().toISOString(),
        });
      }
    }
    if (!toWrite.length) return;
    const storeNames = [MUTATIONS_STORE].concat(toWrite.map(function (w) { return w.storeName; }));
    const uniqueStores = storeNames.filter(function (v, idx, a) { return a.indexOf(v) === idx; });
    await tx(uniqueStores, 'readwrite', function (transaction) {
      const dest = transaction.objectStore(MUTATIONS_STORE);
      toWrite.forEach(function (w) {
        dest.put({
          local_id: w.local_id,
          kind: w.kind,
          payload: w.payload,
          client_timestamp: w.client_timestamp,
        });
        transaction.objectStore(w.storeName).delete(w.local_id);
      });
    });
  }

  async function listMutationsSorted() {
    const rows = await tx(MUTATIONS_STORE, 'readonly', function (transaction) {
      const store = transaction.objectStore(MUTATIONS_STORE);
      return new Promise(function (resolve, reject) {
        const r = store.getAll();
        r.onsuccess = function () { resolve(r.result || []); };
        r.onerror = function () { reject(r.error); };
      });
    });
    rows.sort(function (a, b) {
      const ta = (a.client_timestamp || '');
      const tb = (b.client_timestamp || '');
      if (ta < tb) return -1;
      if (ta > tb) return 1;
      return 0;
    });
    return rows;
  }

  async function removeMutation(localId) {
    return tx(MUTATIONS_STORE, 'readwrite', function (transaction) {
      transaction.objectStore(MUTATIONS_STORE).delete(localId);
    });
  }

  async function syncMutationsBatch() {
    if (!navigator.onLine) return { synced: 0, failed: 0 };
    const rows = await listMutationsSorted();
    if (!rows.length) return { synced: 0, failed: 0 };
    const batch = rows.slice(0, 100);
    const mutations = batch.map(function (r) {
      return { local_id: r.local_id, kind: r.kind, payload: r.payload };
    });
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const res = await fetch('/offline-sync/mutations', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ mutations: mutations }),
    });
    if (!res.ok) throw new Error('Mutation sync failed (' + res.status + ')');
    const json = await res.json();
    const syncedList = Array.isArray(json.synced) ? json.synced : [];
    for (let i = 0; i < syncedList.length; i++) {
      const row = syncedList[i];
      if (row && row.local_id) await removeMutation(row.local_id);
    }
    return {
      synced: syncedList.length,
      failed: Array.isArray(json.failed) ? json.failed.length : 0,
      data: json,
    };
  }

  const api = {
    async queueMutation(kind, payload) {
      const entry = {
        local_id: uuid(),
        kind: kind,
        payload: payload,
        client_timestamp: new Date().toISOString(),
      };
      await tx(MUTATIONS_STORE, 'readwrite', function (transaction) {
        transaction.objectStore(MUTATIONS_STORE).put(entry);
      });
      return entry.local_id;
    },

    queueSale: function (p) { return api.queueMutation('sale_create', p); },
    queueSaleDelete: function (p) { return api.queueMutation('sale_void', p); },
    queueSaleVoid: function (p) { return api.queueSaleDelete(p); },
    queueExpense: function (p) { return api.queueMutation('expense_create', p); },
    queueExpenseUpdate: function (p) { return api.queueMutation('expense_update', p); },
    queueExpenseDelete: function (p) { return api.queueMutation('expense_delete', p); },
    queuePurchaseOrder: function (p) { return api.queueMutation('po_create', p); },
    queuePurchaseOrderUpdate: function (p) { return api.queueMutation('po_update', p); },
    queuePurchaseOrderDelete: function (p) { return api.queueMutation('po_delete', p); },
    queueStockTransfer: function (p) { return api.queueMutation('stock_transfer_create', p); },
    queueProductCreate: function (p) { return api.queueMutation('product_create', p); },
    queueProductUpdate: function (p) { return api.queueMutation('product_update', p); },
    queueProductDelete: function (p) { return api.queueMutation('product_delete', p); },
    queueInventoryAddStock: function (p) { return api.queueMutation('inventory_add_stock', p); },
    queueInventoryAdjustStock: function (p) { return api.queueMutation('inventory_adjust_stock', p); },
    queueExpenseCategoryCreate: function (p) { return api.queueMutation('expense_category_create', p); },
    queueExpenseCategoryUpdate: function (p) { return api.queueMutation('expense_category_update', p); },
    queueExpenseCategoryDelete: function (p) { return api.queueMutation('expense_category_delete', p); },
    queueSupplierCreate: function (p) { return api.queueMutation('supplier_create', p); },
    queueSupplierUpdate: function (p) { return api.queueMutation('supplier_update', p); },
    queueSupplierDelete: function (p) { return api.queueMutation('supplier_delete', p); },
    queueBranchCreate: function (p) { return api.queueMutation('branch_create', p); },
    queueBranchUpdate: function (p) { return api.queueMutation('branch_update', p); },
    queueBranchDelete: function (p) { return api.queueMutation('branch_delete', p); },
    queueUserUpdate: function (p) { return api.queueMutation('user_update', p); },
    queueUserDelete: function (p) { return api.queueMutation('user_delete', p); },
    queueProfileUpdate: function (p) { return api.queueMutation('profile_update', p); },
    queuePoReceive: function (p) { return api.queueMutation('po_receive', p); },
    queuePoRecordPayment: function (p) { return api.queueMutation('po_record_payment', p); },
    queueSalePaymentCreate: function (p) { return api.queueMutation('sale_payment_create', p); },
    queueSalePaymentDelete: function (p) { return api.queueMutation('sale_payment_delete', p); },
    queueLedgerDeliveryCreate: function (p) { return api.queueMutation('ledger_delivery_create', p); },
    queueLedgerPaymentCreate: function (p) { return api.queueMutation('ledger_payment_create', p); },
    queueLedgerDeliveryDelete: function (p) { return api.queueMutation('ledger_delivery_delete', p); },
    queueLedgerPaymentDelete: function (p) { return api.queueMutation('ledger_payment_delete', p); },

    async getPendingCounts() {
      await migrateLegacyToMutations().catch(function () { return null; });
      const rows = await listMutationsSorted();
      const byKind = {};
      for (let i = 0; i < rows.length; i++) {
        const k = rows[i].kind || 'unknown';
        byKind[k] = (byKind[k] || 0) + 1;
      }
      return {
        total: rows.length,
        byKind: byKind,
        sales: (byKind.sale_create || 0) + (byKind.sale_void || 0),
        expenses: (byKind.expense_create || 0) + (byKind.expense_update || 0) + (byKind.expense_delete || 0),
        purchaseOrders: (byKind.po_create || 0) + (byKind.po_update || 0) + (byKind.po_delete || 0) + (byKind.po_receive || 0) + (byKind.po_record_payment || 0),
        transfers: byKind.stock_transfer_create || 0,
      };
    },

    async syncAll() {
      if (!navigator.onLine) {
        const counts = await api.getPendingCounts();
        emitStatus({
          state: 'offline',
          pending: counts.total,
          counts: counts,
          summary: { synced: 0, failed: 0 },
        });
        return { synced: 0, failed: 0, counts: counts };
      }

      if (syncInProgress) {
        return { synced: 0, failed: 0, skipped: true };
      }

      syncInProgress = true;
      let synced = 0;
      let failed = 0;

      try {
        await migrateLegacyToMutations().catch(function () { return null; });

        const before = await api.getPendingCounts();
        emitStatus({
          state: 'syncing',
          pending: before.total,
          counts: before,
          summary: { synced: 0, failed: 0 },
        });

        let guard = 0;
        while (guard < 500) {
          guard += 1;
          const batch = await syncMutationsBatch();
          synced += batch.synced || 0;
          failed += batch.failed || 0;
          if ((batch.synced || 0) === 0) break;
        }

        const after = await api.getPendingCounts();
        emitStatus({
          state: failed > 0 ? 'partial' : 'idle',
          pending: after.total,
          counts: after,
          summary: { synced: synced, failed: failed },
        });

        return { synced: synced, failed: failed, counts: after };
      } catch (err) {
        const counts = await api.getPendingCounts().catch(function () { return { total: 0 }; });
        emitStatus({
          state: 'error',
          pending: counts.total || 0,
          counts: counts,
          summary: { synced: synced, failed: failed + 1 },
          message: err && err.message ? err.message : 'Sync failed',
        });
        return { synced: synced, failed: failed + 1, counts: counts, error: true };
      } finally {
        syncInProgress = false;
      }
    },
  };

  window.CitiOffline = api;

  function emitStatus(detail) {
    window.dispatchEvent(new CustomEvent('citi-offline-sync-status', { detail: detail }));
  }

  async function autoSync() {
    await api.syncAll();
  }

  window.addEventListener('online', autoSync);
  window.addEventListener('load', autoSync);
  setInterval(function () {
    if (navigator.onLine) {
      autoSync();
    } else {
      api.getPendingCounts()
        .then(function (counts) {
          emitStatus({
            state: 'offline',
            pending: counts.total,
            counts: counts,
            summary: { synced: 0, failed: 0 },
          });
        })
        .catch(function () { return null; });
    }
  }, RETRY_MS);
})();
