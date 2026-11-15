/**
 * Autoguardado local (localStorage) para formularios largos de Cote.
 * Toast fijo abajo-derecha, independiente del scroll.
 */
(function (global) {
  'use strict';

  function uuid() {
    if (global.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 't' + Date.now() + '_' + Math.random().toString(36).slice(2, 12);
  }

  function flashIndicator() {
    let el = document.getElementById('cote-autosave-indicator');
    if (!el) {
      el = document.createElement('div');
      el.id = 'cote-autosave-indicator';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
      document.body.appendChild(el);
    }
    const t = new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    el.textContent = 'Autoguardando… ' + t;
    el.classList.add('is-visible');
    clearTimeout(flashIndicator._hide);
    flashIndicator._hide = setTimeout(function () {
      el.classList.remove('is-visible');
    }, 2500);
  }

  function hideIndicator() {
    const el = document.getElementById('cote-autosave-indicator');
    if (el) el.classList.remove('is-visible');
  }

  class CoteFormAutoSave {
    /**
     * @param {object} opts
     * @param {string} [opts.componentId] Livewire id
     * @param {number|string} opts.userId
     * @param {string} opts.formSlug
     * @param {string|number|null} [opts.entityId]
     * @param {string[]} [opts.fields]
     * @param {number} [opts.debounceMs]
     * @param {number} [opts.expirationHours]
     * @param {function|null} [opts.summaryFn]
     * @param {function|null} [opts.hasMeaningfulFn]
     * @param {function|null} [opts.getDomData] modo formulario clásico
     * @param {function|null} [opts.restoreDom] modo formulario clásico
     * @param {string|null} [opts.formRootSelector] contenedor para listeners DOM
     */
    constructor(opts) {
      this.componentId = opts.componentId || null;
      this.userId = String(opts.userId || 0);
      this.formSlug = opts.formSlug;
      this.entityId = opts.entityId != null && opts.entityId !== '' ? String(opts.entityId) : null;
      this.fields = Array.isArray(opts.fields) ? opts.fields : [];
      this.debounceMs = opts.debounceMs || 2500;
      this.expirationHours = opts.expirationHours || 48;
      this.summaryFn = typeof opts.summaryFn === 'function' ? opts.summaryFn : null;
      this.hasMeaningfulFn = typeof opts.hasMeaningfulFn === 'function' ? opts.hasMeaningfulFn : null;
      this.getDomData = typeof opts.getDomData === 'function' ? opts.getDomData : null;
      this.restoreDom = typeof opts.restoreDom === 'function' ? opts.restoreDom : null;
      this.formRootSelector = opts.formRootSelector || null;
      this.saveTimeout = null;
      this.restoring = false;
      this.destroyed = false;
      this.suppressUntil = 0;

      const tabKey = 'cote_autosave_tab_' + this.formSlug + (this.entityId ? '_e' + this.entityId : '');
      let tabDraftId = sessionStorage.getItem(tabKey);
      if (!tabDraftId) {
        tabDraftId = uuid();
        sessionStorage.setItem(tabKey, tabDraftId);
      }
      this.tabDraftId = tabDraftId;

      if (this.entityId) {
        this.storageKey = 'cote_autosave_' + this.formSlug + '_u' + this.userId + '_e' + this.entityId;
      } else {
        this.storageKey = 'cote_autosave_' + this.formSlug + '_u' + this.userId + '_' + this.tabDraftId;
      }
      this.lastSuccessfulSaveKey = 'cote_last_ok_' + this.storageKey;

      this.bind();
      this.checkForSavedData();
    }

    bind() {
      const self = this;

      if (this.componentId && global.Livewire && typeof Livewire.hook === 'function') {
        this._onMorph = function (payload) {
          if (self.destroyed || !payload || !payload.component) return;
          if (payload.component.id === self.componentId) {
            setTimeout(function () { self.triggerSave(); }, 400);
          }
        };
        Livewire.hook('morph.updated', this._onMorph);
      }

      this._onInput = function (e) {
        if (self.destroyed || !e.target) return;
        let root = null;
        if (self.componentId) {
          root = document.querySelector('[wire\\:id="' + self.componentId + '"]');
        } else if (self.formRootSelector) {
          root = document.querySelector(self.formRootSelector);
        }
        if (!root || !root.contains(e.target)) return;
        if (e.target.matches('input, textarea, select')) {
          setTimeout(function () { self.triggerSave(); }, 900);
        }
      };
      document.addEventListener('input', this._onInput, true);
      document.addEventListener('change', this._onInput, true);

      this._onClear = function (payload) {
        const slug = Array.isArray(payload) ? (payload[0] && payload[0].formSlug) : (payload && payload.formSlug);
        if (slug && slug !== self.formSlug) return;
        self.clear(true);
      };

      const bindLw = function () {
        if (typeof Livewire.on === 'function') {
          Livewire.on('autosave:clear', self._onClear);
          Livewire.on('autosave:clear-immediate', self._onClear);
        }
      };
      document.addEventListener('livewire:init', bindLw);
      if (global.Livewire && typeof Livewire.on === 'function') bindLw();

      document.addEventListener('cote-autosave:clear', function (e) {
        const slug = e.detail && e.detail.formSlug;
        if (slug && slug !== self.formSlug) return;
        self.clear(true);
      });
    }

    extractFormData() {
      if (this.getDomData) {
        return this.getDomData();
      }
      if (!global.Livewire || !Livewire.find || !this.componentId) return null;
      const component = Livewire.find(this.componentId);
      if (!component) return null;
      const data = {};
      for (let i = 0; i < this.fields.length; i++) {
        const key = this.fields[i];
        try {
          data[key] = component.get(key);
        } catch (e) {
          data[key] = null;
        }
      }
      if (this.entityId) data._entity_id = this.entityId;
      return data;
    }

    triggerSave() {
      if (this.destroyed || this.restoring) return;
      if (this.suppressUntil && Date.now() < this.suppressUntil) return;
      if (this.isFinishedState()) return;
      const formData = this.extractFormData();
      if (!formData) return;
      if (formData.step === 'listo' || formData.doneId) return;
      if (!this.entityId && !this.hasMeaningfulData(formData)) return;
      this.saveDebounced(formData);
    }

    /** Tras grabar con éxito el componente queda en "listo": no volver a autoguardar ese contenido. */
    isFinishedState() {
      if (!this.componentId || !global.Livewire || !Livewire.find) return false;
      try {
        const component = Livewire.find(this.componentId);
        if (!component) return false;
        if (component.get('step') === 'listo') return true;
        if (component.get('doneId')) return true;
      } catch (e) { /* ignore */ }
      return false;
    }

    hasMeaningfulData(data) {
      if (this.hasMeaningfulFn) return !!this.hasMeaningfulFn(data);
      if (Array.isArray(data.rows) && data.rows.some(function (r) {
        return r && (String(r.vendor || '').trim() || String(r.amount || '').trim() || String(r.number || '').trim() || String(r.category_id || '').trim());
      })) return true;
      if (Array.isArray(data.items) && data.items.some(function (i) {
        return i && (String(i.description || '').trim() || Number(i.amount) > 0 || String(i.vendor_name || '').trim());
      })) return true;
      if (data.accountId || data.account_id) return true;
      if (String(data.amount || '').trim() !== '') return true;
      if (String(data.description || '').trim() !== '') return true;
      return false;
    }

    saveDebounced(formData) {
      const self = this;
      clearTimeout(this.saveTimeout);
      this.saveTimeout = setTimeout(function () { self.save(formData); }, this.debounceMs);
    }

    save(formData) {
      const payload = {
        formSlug: this.formSlug,
        userId: this.userId,
        entityId: this.entityId,
        timestamp: new Date().toISOString(),
        data: formData,
      };
      try {
        localStorage.setItem(this.storageKey, JSON.stringify(payload));
        flashIndicator();
      } catch (e) {
        if (e.name === 'QuotaExceededError') {
          this.cleanOld();
          try {
            localStorage.setItem(this.storageKey, JSON.stringify(payload));
            flashIndicator();
          } catch (e2) { /* ignore */ }
        }
      }
    }

    load() {
      try {
        const raw = localStorage.getItem(this.storageKey);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (!this.isValid(parsed)) {
          this.clear(false);
          return null;
        }
        return parsed;
      } catch (e) {
        return null;
      }
    }

    isValid(saved) {
      if (!saved || !saved.timestamp || !saved.data) return false;
      if (saved.formSlug && saved.formSlug !== this.formSlug) return false;
      if (this.entityId && saved.entityId && String(saved.entityId) !== this.entityId) return false;
      const lastOk = localStorage.getItem(this.lastSuccessfulSaveKey);
      if (lastOk && new Date(saved.timestamp) < new Date(lastOk)) return false;
      const hours = (Date.now() - new Date(saved.timestamp).getTime()) / 3600000;
      return hours < this.expirationHours;
    }

    checkForSavedData() {
      if (this.isFinishedState()) return;
      const saved = this.load();
      if (!saved || !this.hasMeaningfulData(saved.data)) return;
      const self = this;
      const when = new Date(saved.timestamp).toLocaleString('es-CL', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
      });
      let summary = '';
      if (this.summaryFn) summary = this.summaryFn(saved.data) || '';
      else if (Array.isArray(saved.data.rows)) {
        const n = saved.data.rows.filter(function (r) {
          return r && (String(r.vendor || '').trim() || String(r.amount || '').trim());
        }).length;
        if (n) summary = '<li>' + n + ' gasto(s) en la planilla</li>';
      } else if (Array.isArray(saved.data.items)) {
        const n = saved.data.items.filter(function (i) {
          return i && (String(i.description || '').trim() || Number(i.amount) > 0);
        }).length;
        if (n) summary = '<li>' + n + ' gasto(s)</li>';
      }

      const ask = function () {
        if (typeof Swal === 'undefined') {
          if (global.confirm('Hay un borrador del ' + when + '. ¿Restaurar?')) self.doRestore(saved.data);
          else self.clear(true);
          return;
        }
        Swal.fire({
          title: 'Borrador encontrado',
          html: '<p>Autoguardado del <strong>' + when + '</strong>.</p>' +
            (summary ? '<ul class="text-start small mb-0">' + summary + '</ul>' : '') +
            '<p class="text-muted small mt-2 mb-0">¿Desea restaurarlo? (útil tras refrescar o un corte)</p>',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Restaurar',
          cancelButtonText: 'Descartar',
          confirmButtonColor: '#0d6efd',
          allowOutsideClick: false,
        }).then(function (result) {
          if (result.isConfirmed) self.doRestore(saved.data);
          else if (result.dismiss === Swal.DismissReason.cancel) {
            self.clear(true);
            self.callDiscard();
          }
        });
      };
      setTimeout(ask, 600);
    }

    doRestore(data) {
      const self = this;
      this.restoring = true;
      this.clear(false);
      if (this.restoreDom) {
        try {
          this.restoreDom(data);
          if (typeof coteToast === 'function') coteToast('success', 'Borrador restaurado.');
        } catch (e) {
          if (typeof coteToast === 'function') coteToast('error', 'No se pudo restaurar el borrador.');
        }
        this.restoring = false;
        return;
      }
      if (!global.Livewire || !Livewire.find || !this.componentId) {
        this.restoring = false;
        return;
      }
      const component = Livewire.find(this.componentId);
      if (!component) {
        this.restoring = false;
        return;
      }
      component.call('restoreSavedData', data).finally(function () {
        self.restoring = false;
      });
    }

    callDiscard() {
      if (!this.componentId || !global.Livewire || !Livewire.find) return;
      const component = Livewire.find(this.componentId);
      if (component) {
        try { component.call('discardSavedData'); } catch (e) { /* ignore */ }
      }
    }

    clear(markSuccess) {
      clearTimeout(this.saveTimeout);
      this.saveTimeout = null;
      // Evita que un morph posterior al guardar vuelva a escribir el borrador.
      if (markSuccess) this.suppressUntil = Date.now() + 8000;
      try {
        localStorage.removeItem(this.storageKey);
        if (markSuccess) {
          localStorage.setItem(this.lastSuccessfulSaveKey, new Date().toISOString());
          // Borra cualquier borrador del mismo formulario/usuario (p. ej. el que reescribió un morph).
          this.clearSlugDrafts();
        }
      } catch (e) { /* ignore */ }
      hideIndicator();
    }

    clearSlugDrafts() {
      const prefix = 'cote_autosave_' + this.formSlug + '_u' + this.userId;
      try {
        Object.keys(localStorage).forEach(function (key) {
          if (key.startsWith(prefix)) localStorage.removeItem(key);
        });
      } catch (e) { /* ignore */ }
    }

    cleanOld() {
      try {
        const now = Date.now();
        Object.keys(localStorage).forEach(function (key) {
          if (!key.startsWith('cote_autosave_')) return;
          try {
            const data = JSON.parse(localStorage.getItem(key));
            if (!data || !data.timestamp || (now - new Date(data.timestamp).getTime()) / 3600000 > 96) {
              localStorage.removeItem(key);
            }
          } catch (e) {
            localStorage.removeItem(key);
          }
        });
      } catch (e) { /* ignore */ }
    }
  }

  function attach(opts) {
    let inst = null;
    const handle = {
      clear: function (markSuccess) {
        if (inst) inst.clear(!!markSuccess);
      },
      triggerSave: function () {
        if (inst) inst.triggerSave();
      },
      instance: function () { return inst; },
    };
    const boot = function () {
      try {
        inst = new CoteFormAutoSave(opts);
        global.__coteFormAutoSave = inst;
      } catch (e) {
        console.error('[CoteFormAutoSave]', e);
      }
      return inst;
    };
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () { setTimeout(boot, 300); });
    } else {
      setTimeout(boot, 300);
    }
    return handle;
  }

  global.CoteFormAutoSave = { attach: attach, flashIndicator: flashIndicator };
})(window);
