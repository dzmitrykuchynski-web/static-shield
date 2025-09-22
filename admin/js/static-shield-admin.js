document.addEventListener('DOMContentLoaded', () => {
	const tabs = document.querySelectorAll('.nav-tab');
	const contents = document.querySelectorAll('.tab-content');
	const terminal = document.querySelector('.terminal');

	// Tabs
	tabs.forEach(tab => {
		tab.addEventListener('click', () => {
			const target = tab.getAttribute('data-target');

			tabs.forEach(t => t.classList.remove('active'));
			contents.forEach(c => c.style.display = 'none');

			tab.classList.add('active');
			const activeContent = document.getElementById('tab-' + target);
			if (activeContent) {
				activeContent.style.display = 'block';
			}
		});
	});

	// Logs
	async function fetchLogs() {
		try {
			const response = await fetch(ajaxurl + '?action=static_shield_get_logs', {
				credentials: 'same-origin'
			});
			const result = await response.json();
			if (result.success && terminal) {
				terminal.innerHTML = '';
				result.data.logs.forEach(line => {
					let cssClass = 'log-info';
					if (line.includes('[error]')) cssClass = 'log-error';
					else if (line.includes('[warning]')) cssClass = 'log-warning';

					const span = document.createElement('span');
					span.className = cssClass;
					span.innerHTML = line;
					terminal.appendChild(span);
					terminal.appendChild(document.createElement('br'));
				});
				terminal.scrollTop = terminal.scrollHeight;
			}
		} catch (err) {
			console.error('Error fetching logs:', err);
		}
	}

	setInterval(() => {
		const activityTab = document.getElementById('tab-activity-log');
		if (activityTab && activityTab.style.display !== 'none') {
			fetchLogs();
		}
	}, 5000);

	fetchLogs();

	// Cloudflare API Save
	const cfForm = document.querySelector('#static-shield-cf-form');
	if (cfForm) {
		const saveBtn = cfForm.querySelector('input[type="submit"]');

		cfForm.addEventListener('submit', async (e) => {
			e.preventDefault();

			saveBtn.disabled = true;
			const originalText = saveBtn.value;
			saveBtn.value = 'Saving...';

			try {
				const formData = new FormData(cfForm);

				const response = await fetch(ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body: new URLSearchParams({
						action: 'static_shield_save_cf_settings',
						_wpnonce: formData.get('_wpnonce'),
						api_key: formData.get('static_shield_cf_api_key'),
						account_id: formData.get('static_shield_cf_account_id'),
						bucket: formData.get('static_shield_cf_bucket'),
						access_key_id: formData.get('static_shield_cf_access_key_id'),
						secret_access_key: formData.get('static_shield_cf_secret_access_key'),
						cf_worker_url: formData.get('static_shield_cf_worker'),
						use_cf: formData.get('static_shield_use_cf') ? 1 : 0
					})
				});

				const result = await response.json();
				if (result.success) {
					saveBtn.value = 'Saved!';
				} else {
					saveBtn.value = 'Error';
					console.error(result.data.message);
				}
			} catch (err) {
				console.error('Error saving settings:', err);
				saveBtn.value = 'Error';
			} finally {
				setTimeout(() => {
					saveBtn.value = originalText;
					saveBtn.disabled = false;
				}, 1500);
			}
		});
	}

	const customSelects = document.querySelectorAll(".custom-select");

	customSelects.forEach(select => {
		const selected = select.querySelector(".selected");
		const options = select.querySelectorAll(".options li");
		const hiddenInput = select.parentElement.querySelector("input[type=hidden]");

		selected.addEventListener("click", e => {
			e.stopPropagation();
			select.classList.toggle("open");
		});

		options.forEach(option => {
			option.addEventListener("click", e => {
				e.stopPropagation();
				selected.textContent = option.textContent;
				hiddenInput.value = option.dataset.value;
				select.classList.remove("open");
			});
		});
	});

	document.addEventListener("click", () => {
		customSelects.forEach(select => select.classList.remove("open"));
	});

	// DNS Management
	const dnsTableBody = document.querySelector('#dns-records-table tbody');
	const dnsAddForm = document.querySelector('#dns-add-form');

	async function loadDnsRecords() {
		try {
			const response = await fetch(ajaxurl + '?action=static_shield_dns_list', { credentials: 'same-origin' });
			const result = await response.json();

			dnsTableBody.innerHTML = '';

			if (result.success && result.data.records && result.data.records.result) {
				result.data.records.result.forEach(record => {
					const tr = document.createElement('tr');
					tr.innerHTML = `
                    <td>${record.type}</td>
                    <td>${record.name}</td>
                    <td>${record.content}</td>
                    <td>${record.ttl}</td>
                    <td>${record.proxied ? 'Yes' : 'No'}</td>
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
		}
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
	document.querySelector('[data-target="dns-settings"]')?.addEventListener('click', loadDnsRecords);
});
