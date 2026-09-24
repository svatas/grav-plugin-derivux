const DERIVUX_PAGE_TAG = window.__GRAV_PAGE_TAG || 'grav-gravister-derivux--page';
const DERIVUX_VERSION = '0.6.2';

if (!customElements.get(DERIVUX_PAGE_TAG)) {
  customElements.define(DERIVUX_PAGE_TAG, class DerivuxPage extends HTMLElement {
    constructor() {
      super();
      this.attachShadow({ mode: 'open' });
      this.state = {
        step: 1,
        themes: [],
        sourceTheme: '',
        targetSlug: '',
        plan: null,
        result: null,
        error: '',
        busy: false,
      };
      this.unsubscribeLocale = null;
    }

    connectedCallback() {
      this.unsubscribeLocale = window.__GRAV_I18N?.subscribe?.(() => this.render()) || null;
      this.render();
      this.loadThemes();
    }

    disconnectedCallback() {
      if (typeof this.unsubscribeLocale === 'function') {
        this.unsubscribeLocale();
      }
    }

    t(key, params = {}, fallback = key) {
      try {
        const value = window.__GRAV_I18N?.t?.(`PLUGIN_GRAVISTER_DERIVUX.${key}`, params) || fallback;
        return Object.entries(params).reduce(
          (text, [name, item]) => String(text).replaceAll(`{${name}}`, String(item ?? '')),
          String(value),
        );
      } catch {
        return fallback;
      }
    }

    esc(value) {
      return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
    }

    apiUrl(path) {
      const server = String(window.__GRAV_API_SERVER_URL || '').replace(/\/$/, '');
      const prefix = String(window.__GRAV_API_PREFIX || '/api/v1');
      const normalizedPrefix = prefix.startsWith('/') ? prefix : `/${prefix}`;
      const normalizedPath = path.startsWith('/') ? path : `/${path}`;
      return `${server}${normalizedPrefix.replace(/\/$/, '')}${normalizedPath}`;
    }

    async request(path, options = {}) {
      const headers = new Headers(options.headers || {});
      headers.set('Accept', 'application/json');
      if (options.body) {
        headers.set('Content-Type', 'application/json');
      }

      const token = window.__GRAV_API_TOKEN;
      if (token) {
        headers.set('X-API-Token', token);
      }

      const response = await fetch(this.apiUrl(path), {
        ...options,
        headers,
        credentials: 'same-origin',
        cache: 'no-store',
      });

      let payload = null;
      try {
        payload = await response.json();
      } catch {
        payload = null;
      }

      if (!response.ok) {
        const message =
          payload?.error?.detail ||
          payload?.error?.message ||
          payload?.message ||
          `HTTP ${response.status}`;
        throw new Error(message);
      }

      return payload?.data ?? payload;
    }

    adminBase() {
      const path = String(window.location.pathname || '');
      const marker = '/plugin/';
      const index = path.indexOf(marker);
      if (index >= 0) {
        return path.slice(0, index) || '/admin';
      }
      return path.replace(/\/+$/, '') || '/admin';
    }

    themesUrl() {
      return `${this.adminBase()}/themes`;
    }

    themeUrl(slug) {
      return `${this.themesUrl()}/${encodeURIComponent(slug)}`;
    }

    suggest(slug) {
      if (!slug) return '';
      return slug.endsWith('-custom') ? `${slug}-child` : `${slug}-custom`;
    }

    validSlug(slug) {
      return /^[a-z0-9][a-z0-9-]{0,62}$/.test(slug || '');
    }

    async loadThemes() {
      try {
        this.state.busy = true;
        this.state.error = '';
        this.render();

        const data = await this.request('/derivux/themes');
        const themes = Array.isArray(data?.themes) ? data.themes : [];
        this.state.themes = themes;

        const compatibleThemes = themes.filter((item) => item.compatible !== false);
        const preferred = compatibleThemes.find((item) => item.slug === 'typhoon') || compatibleThemes[0] || null;
        this.state.sourceTheme = preferred?.slug || '';
        this.state.targetSlug = this.suggest(this.state.sourceTheme);
      } catch (error) {
        this.state.error = this.t('LOAD_THEMES_FAILED', {}, 'Failed to load themes') + ': ' + error.message;
      } finally {
        this.state.busy = false;
        this.render();
      }
    }

    setStep(step) {
      this.state.step = step;
      this.state.error = '';
      this.render();
      if (step === 3) {
        this.validate();
      }
    }

    async validate() {
      try {
        this.state.busy = true;
        this.state.plan = null;
        this.render();

        this.state.plan = await this.request('/derivux/themes/validate', {
          method: 'POST',
          body: JSON.stringify({
            source_theme: this.state.sourceTheme,
            target_slug: this.state.targetSlug,
          }),
        });
      } catch (error) {
        this.state.error = this.mapError(error.message, 'SUMMARY_FAILED');
      } finally {
        this.state.busy = false;
        this.render();
      }
    }

    async createTheme() {
      try {
        this.state.busy = true;
        this.state.error = '';
        this.render();

        const result = await this.request('/derivux/themes/derive', {
          method: 'POST',
          body: JSON.stringify({
            source_theme: this.state.sourceTheme,
            target_slug: this.state.targetSlug,
            dry_run: false,
          }),
        });

        this.state.result = result;
        this.state.step = 4;
      } catch (error) {
        this.state.error = this.mapError(error.message, 'CREATE_FAILED');
      } finally {
        this.state.busy = false;
        this.render();
      }
    }

    mapError(message, prefixKey) {
      const normalized = String(message || '').toLowerCase();
      let detail = message;

      if (normalized.includes('target theme already exists')) {
        detail = this.t('TARGET_ALREADY_EXISTS', {}, message);
      } else if (normalized.includes('invalid target_slug')) {
        detail = this.t('INVALID_TARGET_SLUG', {}, message);
      } else if (normalized.includes('different from source')) {
        detail = this.t('TARGET_EQUALS_SOURCE', {}, message);
      } else if (normalized.includes('parent theme php class')) {
        detail = this.t('PARENT_CLASS_UNRESOLVED', {}, message);
      } else if (normalized.includes('source theme not found')) {
        detail = this.t('SOURCE_THEME_NOT_FOUND', {}, message);
      }

      return `${this.t(prefixKey)}: ${detail}`;
    }

    nav() {
      const steps = ['STEP_SOURCE', 'STEP_TARGET', 'STEP_SUMMARY', 'STEP_DONE'];
      return `<div class="steps">${steps.map((key, index) =>
        `<span class="step ${this.state.step === index + 1 ? 'active' : ''}">${index + 1}. ${this.esc(this.t(key))}</span>`
      ).join('')}</div>`;
    }

    guidance() {
      const hints = this.state.plan?.build_hints || {};
      const indicators = Array.isArray(hints.indicators) ? hints.indicators : [];
      const details = indicators.length
        ? `<details><summary>${this.esc(this.t('BUILD_HINTS_LABEL'))}</summary><ul>${indicators
            .map((item) => `<li><code>${this.esc(item)}</code></li>`)
            .join('')}</ul></details>`
        : '';

      return `<div class="guidance">
        <h3>${this.esc(this.t('BUILD_GUIDANCE_TITLE'))}</h3>
        <div class="safe">
          <strong>${this.esc(this.t('BUILD_GUIDANCE_SAFE_TITLE'))}</strong>
          <p>${this.esc(this.t('BUILD_GUIDANCE_SAFE_TEXT'))}</p>
        </div>
        <div class="advanced">
          <strong>${this.esc(this.t('BUILD_GUIDANCE_ADVANCED_TITLE'))}</strong>
          <p>${this.esc(this.t('BUILD_GUIDANCE_ADVANCED_PREFIX'))} <code>templates/</code> ${this.esc(this.t('BUILD_GUIDANCE_ADVANCED_SUFFIX'))}</p>
        </div>
        <p class="beginner">${this.esc(this.t('BUILD_GUIDANCE_BEGINNER_HINT'))}</p>
        <details>
          <summary>${this.esc(this.t('BUILD_GUIDANCE_TECHNICAL_SUMMARY'))}</summary>
          <p>${this.esc(this.t('BUILD_GUIDANCE_TECHNICAL_TEXT'))}</p>
          ${details}
        </details>
      </div>`;
    }

    render() {
      const style = `
        :host{display:block;color:var(--dx-text,#1f2937);font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        :host-context([data-theme="dark"]),:host-context(.dark){--dx-card:#1f2937;--dx-text:#f9fafb;--dx-muted:#d1d5db;--dx-border:#374151;--dx-soft:#172033;--dx-accent:#60a5fa;--dx-green:#052e16;--dx-orange:#2b2111}
        :host-context([data-theme="light"]),:host-context(.light){--dx-card:#fff;--dx-text:#111827;--dx-muted:#4b5563;--dx-border:#e5e7eb;--dx-soft:#f3f4f6;--dx-accent:#2563eb;--dx-green:#ecfdf5;--dx-orange:#fffbeb}
        .wrap{background:var(--dx-card,#fff);border:1px solid var(--dx-border,#e5e7eb);border-radius:16px;padding:24px;max-width:1100px;box-shadow:0 12px 30px rgba(0,0,0,.08)}
        h1{margin:0 0 6px;font-size:28px}.version,.lead,.hint{color:var(--dx-muted,#4b5563)}
        .steps{display:flex;gap:8px;flex-wrap:wrap;margin:20px 0}.step{padding:8px 12px;border-radius:999px;border:1px solid var(--dx-border,#e5e7eb);background:var(--dx-soft,#f3f4f6);color:var(--dx-muted,#4b5563);font-size:13px}.step.active{background:var(--dx-accent,#2563eb);border-color:var(--dx-accent,#2563eb);color:white}
        .panel{padding:18px;border:1px solid var(--dx-border,#e5e7eb);border-radius:14px}
        label{display:block;font-weight:600;margin:14px 0 6px}select,input{width:100%;max-width:520px;box-sizing:border-box;border:1px solid var(--dx-border,#e5e7eb);border-radius:10px;padding:10px 12px;background:var(--dx-card,#fff);color:var(--dx-text,#111827)}
        button,a.button{border:0;border-radius:10px;padding:10px 14px;background:var(--dx-accent,#2563eb);color:white;text-decoration:none;cursor:pointer;font-weight:600}button.secondary,a.secondary{background:var(--dx-soft,#f3f4f6);color:var(--dx-text,#111827);border:1px solid var(--dx-border,#e5e7eb)}button:disabled{opacity:.5;cursor:not-allowed}
        .buttons{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.error{border-left:4px solid #ef4444;background:rgba(239,68,68,.08);padding:12px;border-radius:8px;margin:12px 0}
        .guidance{border:1px solid var(--dx-border,#e5e7eb);border-radius:14px;padding:16px;margin:16px 0;background:var(--dx-soft,#f3f4f6)}.safe{background:var(--dx-green,#ecfdf5);border-left:4px solid #10b981;padding:12px;border-radius:10px;margin:10px 0}.advanced{background:var(--dx-orange,#fffbeb);border-left:4px solid #f59e0b;padding:12px;border-radius:10px;margin:10px 0}.beginner{font-weight:600;margin:12px 0}
        code,pre{background:var(--dx-soft,#f3f4f6);border:1px solid var(--dx-border,#e5e7eb);border-radius:8px}code{padding:2px 5px}pre{padding:12px;overflow:auto;white-space:pre-wrap}.busy{opacity:.7}
      `;

      let content = '';
      const error = this.state.error ? `<div class="error">${this.esc(this.state.error)}</div>` : '';

      if (this.state.step === 1) {
        content = `${this.nav()}<div class="panel">
          <h2>${this.esc(this.t('SELECT_PARENT_THEME'))}</h2>
          ${error}
          <label for="src">${this.esc(this.t('PARENT_THEME'))}</label>
          <select id="src" ${this.state.busy ? 'disabled' : ''}>
            ${this.state.themes.map((theme) =>
              `<option value="${this.esc(theme.slug)}" ${theme.slug === this.state.sourceTheme ? 'selected' : ''} ${theme.compatible === false ? 'disabled' : ''}>${this.esc(theme.slug)}${theme.compatible === false ? ` (${this.esc(this.t('INCOMPATIBLE_PARENT'))})` : ''}</option>`
            ).join('')}
          </select>
          <p class="hint">${this.esc(this.t('SOURCE_HINT'))}</p>
          <div class="buttons"><button id="next" ${!this.state.sourceTheme || this.state.busy ? 'disabled' : ''}>${this.esc(this.t('NEXT'))}</button></div>
        </div>`;
      } else if (this.state.step === 2) {
        const valid = this.validSlug(this.state.targetSlug) && this.state.targetSlug !== this.state.sourceTheme;
        content = `${this.nav()}<div class="panel">
          <h2>${this.esc(this.t('TARGET_TITLE'))}</h2>
          ${error}
          <p><b>${this.esc(this.t('PARENT_THEME'))}:</b> <code>${this.esc(this.state.sourceTheme)}</code></p>
          <label for="target">${this.esc(this.t('TARGET_SLUG'))}</label>
          <input id="target" value="${this.esc(this.state.targetSlug)}">
          <p class="hint">${this.esc(this.t('RECOMMENDATION'))}: <code>${this.esc(this.suggest(this.state.sourceTheme))}</code></p>
          <ul>
            <li>${this.esc(this.t('CREATE_CHILD_THEME'))}</li>
            <li>${this.esc(this.t('CONFIG_BASED_ON_PARENT'))}</li>
            <li>${this.esc(this.t('BLUEPRINT_WITH_CUSTOM_IDENTITY'))}</li>
            <li>${this.esc(this.t('INHERITANCE_STREAMS'))}</li>
          </ul>
          <div class="buttons">
            <button class="secondary" id="back">${this.esc(this.t('BACK'))}</button>
            <button id="next" ${valid ? '' : 'disabled'}>${this.esc(this.t('NEXT'))}</button>
          </div>
        </div>`;
      } else if (this.state.step === 3) {
        const operations = this.state.plan?.operations || [];
        const report = this.state.plan?.report || [];
        content = `${this.nav()}<div class="panel">
          <h2>${this.esc(this.t('SUMMARY_TITLE'))}</h2>
          ${error}
          ${this.state.plan ? `
            <p><code>${this.esc(this.state.sourceTheme)}</code> → <code>${this.esc(this.state.targetSlug)}</code></p>
            <ul>
              <li>${this.esc(this.t('PARENT_UNCHANGED'))}</li>
              <li>${this.esc(this.t('CHILD_CREATED'))}</li>
              <li>${this.esc(this.t('TEMPLATES_NOT_COPIED'))}</li>
              <li>${this.esc(this.t('NOT_ACTIVATED_AUTOMATICALLY'))}</li>
              <li>${this.esc(this.t('PREVIEW_GENERATED'))}</li>
            </ul>
            ${this.guidance()}
            <details>
              <summary>${this.esc(this.t('TECHNICAL_DETAILS'))}</summary>
              <h3>${this.esc(this.t('REPORT'))}</h3>
              <pre>${this.esc(JSON.stringify(report, null, 2))}</pre>
              <h3>${this.esc(this.t('OPERATIONS'))}</h3>
              <pre>${this.esc(JSON.stringify(operations, null, 2))}</pre>
            </details>
          ` : ''}
          <div class="buttons">
            <button class="secondary" id="back" ${this.state.busy ? 'disabled' : ''}>${this.esc(this.t('BACK'))}</button>
            <button id="create" ${!this.state.plan || this.state.busy ? 'disabled' : ''}>${this.esc(this.t('CREATE_THEME'))}</button>
          </div>
        </div>`;
      } else {
        content = `${this.nav()}<div class="panel">
          <h2>${this.esc(this.t('DONE_TITLE'))}</h2>
          ${error}
          <p>${this.esc(this.t('CHILD_THEME_CREATED'))}: <code>${this.esc(this.state.targetSlug)}</code></p>
          <ul>
            <li>${this.esc(this.t('ACTIVATION_NOT_AUTOMATIC'))}</li>
            <li>${this.esc(this.t('CHECK_CONFIG_BEFORE_ACTIVATION'))}</li>
          </ul>
          ${this.guidance()}
          <div class="buttons">
            <a class="button" href="${this.esc(this.themesUrl())}">${this.esc(this.t('GO_TO_THEMES'))}</a>
            <a class="button secondary" href="${this.esc(this.themeUrl(this.state.targetSlug))}">${this.esc(this.t('OPEN_CREATED_THEME'))}</a>
            <button class="secondary" id="again">${this.esc(this.t('CREATE_ANOTHER'))}</button>
          </div>
        </div>`;
      }

      this.shadowRoot.innerHTML = `<style>${style}</style><div class="wrap ${this.state.busy ? 'busy' : ''}">
        <h1>${this.esc(this.t('TITLE', {}, 'Derivux by Gravister'))}</h1>
        <div class="version">${this.esc(this.t('VERSION_LABEL', {}, DERIVUX_VERSION))}</div>
        <div class="lead">${this.esc(this.t('LEAD'))}</div>
        <div id="app">${content}</div>
      </div>`;

      this.bind();
    }

    bind() {
      this.shadowRoot.querySelector('#src')?.addEventListener('change', (event) => {
        this.state.sourceTheme = event.target.value;
        this.state.targetSlug = this.suggest(this.state.sourceTheme);
        this.render();
      });

      this.shadowRoot.querySelector('#target')?.addEventListener('input', (event) => {
        this.state.targetSlug = event.target.value.trim();
        this.render();
      });

      this.shadowRoot.querySelector('#back')?.addEventListener('click', () => {
        this.setStep(Math.max(1, this.state.step - 1));
      });

      this.shadowRoot.querySelector('#next')?.addEventListener('click', () => {
        this.setStep(Math.min(3, this.state.step + 1));
      });

      this.shadowRoot.querySelector('#create')?.addEventListener('click', () => this.createTheme());

      this.shadowRoot.querySelector('#again')?.addEventListener('click', () => {
        this.state.step = 1;
        this.state.plan = null;
        this.state.result = null;
        this.state.error = '';
        this.state.targetSlug = this.suggest(this.state.sourceTheme);
        this.render();
      });
    }
  });
}
