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
		const apiInput = cfForm.querySelector('input[name="static_shield_cf_api_key"]');

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
						action: 'static_shield_save_cf_token',
						_wpnonce: formData.get('_wpnonce'),
						token: formData.get('static_shield_cf_api_key')
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
				console.error('Error saving token:', err);
				saveBtn.value = 'Error';
			} finally {
				setTimeout(() => {
					saveBtn.value = originalText;
					saveBtn.disabled = false;
				}, 1500);
			}
		});
	}
});
