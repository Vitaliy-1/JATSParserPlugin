/**
 * @file plugins/generic/jatsParser/js/jatsParserWorkflow.js
 *
 * Copyright (c) 2017-2024 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief Vue.js component for the "JATS Full Text" custom tab in the editorial workflow.
 *
 * XML files are read from the Production stage (Production-Ready files).
 * The native OJS JATS tab is not used.
 *
 * Registers the component and:
 * 1. Extends getMenuItems  → adds a custom "JATS Full Text" secondary tab under Publication.
 * 2. Extends getPrimaryItems → injects the component into that tab.
 */

pkp.registry.registerComponent('JatsParserWorkflowSection', {
	name: 'JatsParserWorkflowSection',

	template:
		'<div style="padding:1rem 0;">' +

		// ── Loading ──────────────────────────────────────────────────────────
		'  <div v-if="isLoading" style="color:#888;padding:0.5rem 0;">' +
		'    {{ t(\'loading\') }}&hellip;' +
		'  </div>' +

		// ── Main content ─────────────────────────────────────────────────────
		'  <div v-else>' +

		// No XML files in Production
		'    <div v-if="productionFiles.length === 0"' +
		'         style="padding:1rem;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;color:#666;font-size:0.875rem;">' +
		'      {{ t(\'noProductionFiles\') }}' +
		'    </div>' +

		// File selector
		'    <div v-else style="margin-bottom:1.25rem;">' +
		'      <div style="font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:#333;">' +
		'        {{ t(\'selectFile\') }}' +
		'      </div>' +
		'      <div v-for="file in productionFiles" :key="file.id"' +
		'           style="display:flex;align-items:center;margin-bottom:0.35rem;">' +
		'        <input type="radio"' +
		'               :id="\'jatsFile-\' + file.id"' +
		'               :value="file.id"' +
		'               v-model="selectedRadioFileId"' +
		'               style="margin-right:0.5rem;cursor:pointer;">' +
		'        <label :for="\'jatsFile-\' + file.id"' +
		'               style="font-size:0.875rem;cursor:pointer;margin:0;">' +
		'          {{ file.name }}' +
		'          <span v-if="file.id == selectedFileId"' +
		'                style="color:#2e7d32;font-size:0.8rem;margin-left:0.4rem;">&#10003; {{ t(\'currentlyProcessed\') }}</span>' +
		'        </label>' +
		'      </div>' +
		'    </div>' +

		// Action buttons
		'    <div v-if="productionFiles.length > 0"' +
		'         style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.75rem;">' +
		'      <pkp-button' +
		'        :is-busy="isBusy"' +
		'        :disabled="!selectedRadioFileId || !canEdit"' +
		'        @click="process">' +
		'        {{ selectedRadioFileId && selectedRadioFileId == selectedFileId ? t(\'reprocess\') : t(\'process\') }}' +
		'      </pkp-button>' +
		'      <pkp-button' +
		'        v-if="isProcessed"' +
		'        :is-busy="isBusy"' +
		'        :disabled="!canEdit"' +
		'        :is-warnable="true"' +
		'        @click="clear">' +
		'        {{ t(\'clearFullText\') }}' +
		'      </pkp-button>' +
		'    </div>' +

		// Feedback message
		'    <div v-if="message"' +
		'         :style="messageStyle"' +
		'         style="margin-bottom:0.75rem;padding:0.5rem 0.75rem;border-radius:3px;font-size:0.875rem;">' +
		'      {{ message }}' +
		'    </div>' +

		// Assets section
		'    <div v-if="assets.length > 0"' +
		'         style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #eee;">' +
		'      <div style="font-size:0.875rem;font-weight:600;margin-bottom:0.4rem;color:#555;text-transform:uppercase;letter-spacing:0.04em;">' +
		'        {{ t(\'assetsTitle\') }}' +
		'      </div>' +
		'      <ul style="margin:0 0 0.5rem;padding-left:1.25rem;">' +
		'        <li v-for="asset in assets" :key="asset.id"' +
		'            style="font-size:0.875rem;color:#333;">{{ asset.name }}</li>' +
		'      </ul>' +
		'    </div>' +

		// Upload hint
		'    <p style="margin-top:1rem;margin-bottom:0;font-size:0.8rem;color:#aaa;">' +
		'      {{ t(\'uploadInfo\') }}' +
		'    </p>' +

		'  </div>' + // end v-else
		'</div>',

	props: {
		submission: {
			type: Object,
			required: true,
		},
		publication: {
			type: Object,
			required: true,
		},
		canEdit: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			isLoading: true,
			isBusy: false,
			message: '',
			messageIsError: false,
			productionFiles: [],    // PRODUCTION_READY XML files
			assets: [],             // dependent image files of the selected XML
			selectedFileId: null,   // file currently stored in publication settings
			selectedFileName: null,
			selectedRadioFileId: null, // user's radio selection in the UI
		};
	},

	computed: {
		isProcessed() {
			return !!this.selectedFileId;
		},

		messageStyle() {
			return this.messageIsError
				? 'background:#fde8e8;color:#c00;'
				: 'background:#e8f5e9;color:#2e7d32;';
		},
	},

	mounted() {
		this.loadFiles();
	},

	methods: {
		t(key) {
			var cfg = window.jatsParserConfig;
			if (cfg && cfg.locale && cfg.locale[key] !== undefined) {
				return cfg.locale[key];
			}
			return key;
		},

		/**
		 * Load Production-Ready XML files and asset list from the server.
		 */
		async loadFiles() {
			var cfg = window.jatsParserConfig;
			if (!cfg || !cfg.filesUrl) {
				this.isLoading = false;
				return;
			}

			this.isLoading = true;

			var formData = new FormData();
			formData.append('submissionId', this.submission.id);
			formData.append('publicationId', this.publication.id);
			formData.append('csrfToken', pkp.currentUser.csrfToken);

			try {
				var response = await fetch(cfg.filesUrl, {
					method: 'POST',
					body: formData,
				});
				var data = await response.json();

				if (data.status === true && data.content) {
					this.productionFiles    = data.content.productionFiles  || [];
					this.assets             = data.content.assets           || [];
					this.selectedFileId     = data.content.selectedFileId   || null;
					this.selectedFileName   = data.content.selectedFileName || null;
					// Pre-select the currently processed file in the radio list
					this.selectedRadioFileId = this.selectedFileId;
				}
			} catch (e) {
				// Silently ignore load errors
			}

			this.isLoading = false;
		},

		/**
		 * Process the selected Production-Ready XML file.
		 */
		async process() {
			if (!this.selectedRadioFileId) {
				return;
			}

			this.isBusy = true;
			this.message = '';
			this.messageIsError = false;

			var cfg = window.jatsParserConfig;
			var formData = new FormData();
			formData.append('submissionId', this.submission.id);
			formData.append('publicationId', this.publication.id);
			formData.append('fileId', this.selectedRadioFileId);
			formData.append('csrfToken', pkp.currentUser.csrfToken);

			try {
				var response = await fetch(cfg.processUrl, {
					method: 'POST',
					body: formData,
				});
				var data = await response.json();

				if (data.status === true) {
					this.message = this.t('processSuccess');
					this.messageIsError = false;

					// Update local state so isProcessed and the check-mark refresh
					if (data.content && data.content.fileId) {
						this.selectedFileId = data.content.fileId;
						if (!this.publication['jatsParser::fullTextFileId']) {
							this.publication['jatsParser::fullTextFileId'] = {};
						}
						this.publication['jatsParser::fullTextFileId'][data.content.locale] =
							data.content.fileId;
					}

					// Reload to refresh isSelected flags and asset list
					await this.loadFiles();
				} else if (data.errorCode === 'noJatsFile') {
					this.message = this.t('noJatsFile');
					this.messageIsError = true;
				} else {
					this.message = this.t('processError');
					this.messageIsError = true;
				}
			} catch (e) {
				this.message = this.t('processError');
				this.messageIsError = true;
			}

			this.isBusy = false;
		},

		/**
		 * Remove the generated full text (the XML file in Production is kept).
		 */
		async clear() {
			if (!confirm(this.t('confirmClear'))) {
				return;
			}

			this.isBusy = true;
			this.message = '';
			this.messageIsError = false;

			var cfg = window.jatsParserConfig;
			var formData = new FormData();
			formData.append('submissionId', this.submission.id);
			formData.append('publicationId', this.publication.id);
			formData.append('csrfToken', pkp.currentUser.csrfToken);

			try {
				var response = await fetch(cfg.clearUrl, {
					method: 'POST',
					body: formData,
				});
				var data = await response.json();

				if (data.status === true) {
					this.message = this.t('clearSuccess');
					this.messageIsError = false;
					// Clear processed-file state; keep radio selection so the user
					// can immediately re-process if desired
					this.selectedFileId   = null;
					this.selectedFileName = null;
					this.assets           = [];
					this.publication['jatsParser::fullTextFileId'] = null;
					// Refresh the isSelected flags in productionFiles
					this.productionFiles = this.productionFiles.map(function (f) {
						return Object.assign({}, f, {isSelected: false});
					});
				} else {
					this.message = this.t('clearError');
					this.messageIsError = true;
				}
			} catch (e) {
				this.message = this.t('clearError');
				this.messageIsError = true;
			}

			this.isBusy = false;
		},
	},
});

/**
 * Add a custom "JATS Full Text" secondary tab under the Publication primary menu.
 */
pkp.registry.storeExtendFn('workflow', 'getMenuItems', function (originalItems, args) {
	var tabLabel =
		window.jatsParserConfig &&
		window.jatsParserConfig.locale &&
		window.jatsParserConfig.locale.tabLabel
			? window.jatsParserConfig.locale.tabLabel
			: 'JATS Full Text';
	return originalItems.map(function (item) {
		if (item.key === 'publication' && Array.isArray(item.items)) {
			return Object.assign({}, item, {
				items: item.items.concat([
					{
						key: 'publication_jatsparser',
						label: tabLabel,
						state: {
							primaryMenuItem: 'publication',
							secondaryMenuItem: 'jatsparser',
							title: tabLabel,
						},
					},
				]),
			});
		}
		return item;
	});
});

/**
 * Inject JatsParserWorkflowSection into the custom "jatsparser" publication tab.
 */
pkp.registry.storeExtendFn(
	'workflow',
	'getPrimaryItems',
	function (originalItems, args) {
		if (
			args.selectedMenuState &&
			args.selectedMenuState.primaryMenuItem === 'publication' &&
			args.selectedMenuState.secondaryMenuItem === 'jatsparser'
		) {
			return [
				{
					component: 'JatsParserWorkflowSection',
					props: {
						submission: args.submission,
						publication:
							args.submission.publications.find(function (p) {
								return p.status === 1;
							}) ||
							args.submission.publications[
								args.submission.publications.length - 1
							],
						canEdit: args.permissions
							? args.permissions.canEditPublication
							: false,
					},
				},
			];
		}

		return originalItems;
	},
);
