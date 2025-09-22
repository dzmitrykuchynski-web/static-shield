document.addEventListener('DOMContentLoaded', () => {
	const tabs = document.querySelectorAll('.nav-tab');
	const contents = document.querySelectorAll('.tab-content');
	const terminal = document.querySelector('.terminal');

	// Tabs
	function openTab(target) {
		// Toggle active class for tabs
		tabs.forEach(t => t.classList.toggle('active', t.getAttribute('data-target') === target));

		contents.forEach(c => {
			c.style.display = (c.id === 'tab-' + target) ? 'block' : 'none';
		});

		// Update hash without scrolling
		try {
			history.replaceState(null, '', '#' + target);
		} catch (err) {
			// ignore in environments that disallow replaceState
			location.hash = target;
		}

		if (target === 'activity-log') {
			fetchLogs();
		} else if (target === 'dns-settings') {
			loadDnsRecords();
		}
	}

	tabs.forEach(tab => {
		tab.addEventListener('click', (e) => {
			e.preventDefault();
			const target = tab.getAttribute('data-target');
			openTab(target);
		});
	});

	(function restoreInitialTab() {
		const hash = (location.hash || '').replace(/^#/, '');
		if (hash && document.querySelector('.nav-tab[data-target="' + hash + '"]')) {
			openTab(hash);
			return;
		}

		const activeTab = document.querySelector('.nav-tab.active');
		if (activeTab) {
			openTab(activeTab.getAttribute('data-target'));
			return;
		}

		openTab('activity-log');
	})();

	// Logs
	async function fetchLogs() {
		if (!terminal) return;

		try {
			const response = await fetch(ajaxurl + '?action=static_shield_get_logs', {
				credentials: 'same-origin'
			});
			const result = await response.json();
			terminal.innerHTML = '';

			if (result.success && result.data && Array.isArray(result.data.logs)) {
				result.data.logs.forEach(line => {
					let cssClass = 'log-info';
					if (line.includes('[error]')) cssClass = 'log-error';
					else if (line.includes('[warning]')) cssClass = 'log-warning';

					const span = document.createElement('span');
					span.className = cssClass;
					span.textContent = line;
					terminal.appendChild(span);
					terminal.appendChild(document.createElement('br'));
				});
				terminal.scrollTop = terminal.scrollHeight;
			} else {
				terminal.innerHTML = '<span class="log-info">No logs available.</span>';
			}
		} catch (err) {
			console.error('Error fetching logs:', err);
		}
	}

	setInterval(() => {
		const activityTabContent = document.getElementById('tab-activity-log');
		if (activityTabContent && activityTabContent.style.display !== 'none') {
			fetchLogs();
		}
	}, 5000);

	// Handle Domain Settings save
	const domainForm = document.querySelector('#static-shield-domain-form');
	if (domainForm) {
		const saveBtn = domainForm.querySelector('input[type="submit"]');

		domainForm.addEventListener('submit', async e => {
			e.preventDefault();
			saveBtn.disabled = true;
			const originalText = saveBtn.value;
			saveBtn.value = 'Saving...';

			try {
				const formData = new FormData(domainForm);
				const response = await fetch(ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body: new URLSearchParams({
						action: 'static_shield_save_domain_settings',
						_wpnonce: formData.get('_wpnonce'),
						api_key: formData.get('static_shield_cf_api_key'),
						cf_worker_url: formData.get('static_shield_cf_worker')
					})
				});

				const result = await response.json();
				saveBtn.value = result.success ? 'Saved!' : 'Error';
			} catch (err) {
				console.error('Error saving domain settings:', err);
				saveBtn.value = 'Error';
			} finally {
				setTimeout(() => {
					saveBtn.value = originalText;
					saveBtn.disabled = false;
				}, 1500);
			}
		});
	}

	// Handle Worker Settings save
	const workerForm = document.querySelector('#static-shield-worker-form');
	if (workerForm) {
		const saveBtn = workerForm.querySelector('input[type="submit"]');

		workerForm.addEventListener('submit', async e => {
			e.preventDefault();
			saveBtn.disabled = true;
			const originalText = saveBtn.value;
			saveBtn.value = 'Saving...';

			try {
				const formData = new FormData(workerForm);
				const response = await fetch(ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body: new URLSearchParams({
						action: 'static_shield_save_worker_settings',
						_wpnonce: formData.get('_wpnonce'),
						account_id: formData.get('static_shield_cf_account_id'),
						bucket: formData.get('static_shield_cf_bucket'),
						access_key_id: formData.get('static_shield_cf_access_key_id'),
						secret_access_key: formData.get('static_shield_cf_secret_access_key'),
						use_cf: formData.get('static_shield_use_cf') ? 1 : 0
					})
				});

				const result = await response.json();
				saveBtn.value = result.success ? 'Saved!' : 'Error';
			} catch (err) {
				console.error('Error saving worker settings:', err);
				saveBtn.value = 'Error';
			} finally {
				setTimeout(() => {
					saveBtn.value = originalText;
					saveBtn.disabled = false;
				}, 1500);
			}
		});
	}

	const customSelects = document.querySelectorAll('.custom-select');

	customSelects.forEach(select => {
		const selected = select.querySelector('.selected');
		const options = select.querySelectorAll('.options li');
		const hiddenInput = select.parentElement.querySelector('input[type=hidden]');

		selected.addEventListener('click', e => {
			e.stopPropagation();
			select.classList.toggle('open');
		});

		options.forEach(option => {
			option.addEventListener('click', e => {
				e.stopPropagation();
				selected.textContent = option.textContent;
				hiddenInput.value = option.dataset.value;
				select.classList.remove('open');

				customSelects.forEach(s => { if (s !== select) s.classList.remove('open'); });
			});
		});
	});

	document.addEventListener('click', () => {
		customSelects.forEach(select => select.classList.remove('open'));
	});

	// DNS Management
	const dnsTableBody = document.querySelector('#dns-records-table tbody');
	const dnsAddForm = document.querySelector('#dns-add-form');

	async function loadDnsRecords() {
		try {
			const response = await fetch(ajaxurl + '?action=static_shield_dns_list', { credentials: 'same-origin' });
			const result = await response.json();

			if (!dnsTableBody) return;

			dnsTableBody.innerHTML = '';

			if (result.success && result.data && result.data.records && Array.isArray(result.data.records.result)) {
				result.data.records.result.forEach(record => {
					const tr = document.createElement('tr');
					tr.innerHTML = `
                        <td>${escapeHtml(record.type)}</td>
                        <td>${escapeHtml(record.name)}</td>
                        <td>${escapeHtml(record.content)}</td>
                        <td>${escapeHtml(record.ttl)}</td>
                        <td>${record.proxied ? '<span class="toggle-label-on">Yes</span>' : '<span class="toggle-label-off">No</span>'}</td>
                        <td>
                            <button data-id="${record.id}" class="button delete-dns">Delete</button>
                        </td>
                    `;
					dnsTableBody.appendChild(tr);
				});
			} else {
				dnsTableBody.innerHTML = '<tr><td colspan="6">No records found.</td></tr>';
			}
		} catch (err) {
			console.error('Error loading DNS records:', err);
			if (dnsTableBody) dnsTableBody.innerHTML = '<tr><td colspan="6">Error loading records.</td></tr>';
		}
	}

	// Helper to escape HTML in inserts
	function escapeHtml(str = '') {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	// Handle Add Record
	if (dnsAddForm) {
		dnsAddForm.addEventListener('submit', async e => {
			e.preventDefault();
			const formData = new FormData(dnsAddForm);

			const response = await fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: new URLSearchParams({
					action: 'static_shield_dns_add',
					type: formData.get('type'),
					name: formData.get('name'),
					content: formData.get('content'),
					ttl: formData.get('ttl'),
					proxied: formData.get('proxied') ? 1 : 0
				})
			});

			const result = await response.json();
			if (result.success) {
				dnsAddForm.reset();
				loadDnsRecords();
			} else {
				alert('Error adding record: ' + (result.data?.message || 'Unknown'));
			}
		});
	}

	// Handle Delete
	dnsTableBody?.addEventListener('click', async e => {
		if (e.target.classList.contains('delete-dns')) {
			const id = e.target.getAttribute('data-id');

			if (!confirm('Delete this DNS record?')) return;

			const response = await fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: new URLSearchParams({
					action: 'static_shield_dns_delete',
					id
				})
			});

			const result = await response.json();
			if (result.success) {
				loadDnsRecords();
			} else {
				alert('Error deleting record: ' + (result.data?.message || 'Unknown'));
			}
		}
	});

	// Autoload records when DNS tab is opened
	document.querySelector('[data-target="dns-settings"]')?.addEventListener('click', () => loadDnsRecords());
});
