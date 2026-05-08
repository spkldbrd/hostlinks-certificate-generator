(function () {
	'use strict';

	var eventsById   = {};   // keyed by String(id) — used only for PDF requests
	var eventsInOrder = [];  // ordered array matching the <option> elements after the placeholder

	function el(id) {
		return document.getElementById(id);
	}

	function status(msg, isErr) {
		var s = el('hlc-status');
		if (!s) return;
		s.textContent = msg || '';
		s.className = 'hlc-status' + (isErr ? ' hlc-status--error' : ' hlc-status--ok');
	}

	function base64ToBlob(b64, mime) {
		var bin = atob(b64);
		var len = bin.length;
		var arr = new Uint8Array(len);
		for (var i = 0; i < len; i++) arr[i] = bin.charCodeAt(i);
		return new Blob([arr], { type: mime });
	}

	function hash32(seed) {
		var hash = 0;
		var i;
		for (i = 0; i < seed.length; i++) {
			hash = ((hash * 31) + seed.charCodeAt(i)) >>> 0;
		}
		return hash;
	}

	function certificateId(name, variant, dateKey) {
		var dk = dateKey || '';
		var seed = name + '-' + variant + '-' + dk;
		var h = hash32(seed);
		var year = dk.length >= 4 ? dk.slice(0, 4) : String(new Date().getFullYear());
		if (!/^\d{4}$/.test(year)) year = String(new Date().getFullYear());
		var suffix = String(h % 99999).padStart(5, '0');
		return 'GWU-' + year + '-' + suffix;
	}

	function logoForVariant(variant) {
		if (!hlcData.media) return '';
		if (variant === 'subaward') {
			return hlcData.media.logoSub || hlcData.media.logoGm || hlcData.media.logoGw;
		}
		if (variant === 'grant_management') {
			return hlcData.media.logoGm || hlcData.media.logoGw;
		}
		return hlcData.media.logoGw;
	}

	function sealForVariant(variant) {
		if (!hlcData.media) return '';
		if (variant === 'grant_management' || variant === 'subaward') {
			return hlcData.media.sealGm || hlcData.media.sealGw || '';
		}
		return hlcData.media.sealGw || hlcData.media.sealGm || '';
	}

	function currentVariant() {
		var classEl = el('hlc-filter-class');
		var v = classEl && classEl.value ? classEl.value : 'grant_writing';
		if (v !== 'grant_writing' && v !== 'grant_management' && v !== 'subaward') {
			v = 'grant_writing';
		}
		return v;
	}

	function syncPreview() {
		if (typeof hlcData === 'undefined' || !hlcData.workshopCopy) return;

		var select = el('hlc-event');
		var nameIn = el('hlc-participant');
		var agencyIn = el('hlc-agency');

		// Use selectedIndex so the lookup is position-based (immune to duplicate / null IDs).
		// selectedIndex 0 = placeholder "— Select an event —"; 1+ = actual events.
		var rawIdx = select ? select.selectedIndex : -1;
		var ev = (rawIdx > 0 && eventsInOrder[rawIdx - 1]) ? eventsInOrder[rawIdx - 1] : null;

		var name = (nameIn && nameIn.value) ? nameIn.value.trim() : '';
		var agency = (agencyIn && agencyIn.value) ? agencyIn.value.trim() : '';
		// Variant is driven by the class-type selector, not the event row.
		var variant = currentVariant();
		var copy = hlcData.workshopCopy[variant] || hlcData.workshopCopy.grant_writing;

		var prLogo = el('hlc-pr-logo');
		if (prLogo) {
			prLogo.src = logoForVariant(variant);
			prLogo.style.visibility = prLogo.src ? 'visible' : 'hidden';
		}

		var prSeal = el('hlc-pr-seal');
		if (prSeal) {
			var sealSrc = sealForVariant(variant);
			prSeal.src = sealSrc;
			prSeal.style.visibility = sealSrc ? 'visible' : 'hidden';
		}

		var prName = el('hlc-pr-name');
		if (prName) prName.textContent = name || 'Recipient Name';

		var prAg = el('hlc-pr-agency');
		if (prAg) {
			if (agency) {
				prAg.textContent = agency;
				prAg.hidden = false;
			} else {
				prAg.textContent = '';
				prAg.hidden = true;
			}
		}

		var prBody = el('hlc-pr-body');
		if (prBody) prBody.textContent = copy.body || '';

		var prProg = el('hlc-pr-program');
		if (prProg && copy.title && copy.hours) {
			prProg.textContent = copy.title + ' · ' + copy.hours;
		}

		var dateLong = ev && ev.completion_date_long ? ev.completion_date_long : '—';
		var prDate = el('hlc-pr-datelong');
		if (prDate) prDate.textContent = dateLong;

		var dateKey = ev && ev.date_key ? ev.date_key : '';
		var cid = certificateId(name || ' ', variant, dateKey);
		var prCid = el('hlc-pr-cert-id');
		if (prCid) prCid.textContent = cid;

		var metaCert = el('hlc-pr-meta-cert');
		if (metaCert) metaCert.textContent = 'Certificate No. ' + cid;

		var mini = el('hlc-mini-meta');
		if (mini && ev) {
			mini.hidden = false;
			var mt = el('hlc-mini-type');
			var md = el('hlc-mini-date');
			if (mt) mt.textContent = ev.type_name || '';
			if (md) md.textContent = ev.date_label || ev.start || '';
		} else if (mini) {
			mini.hidden = true;
		}

		flashPreview();
	}

	function eventsQueryUrl() {
		var yEl = el('hlc-filter-year');
		var mEl = el('hlc-filter-month');
		var year = yEl && yEl.value ? parseInt(yEl.value, 10) : new Date().getFullYear();
		var month = mEl && mEl.value !== '' ? parseInt(mEl.value, 10) : 0;
		if (isNaN(year) || year < 1990 || year > 2100) year = new Date().getFullYear();
		if (isNaN(month) || month < 0 || month > 12) month = 0;
		var q = new URLSearchParams();
		q.set('year', String(year));
		q.set('month', String(month));
		q.set('class', currentVariant());
		return hlcData.restUrl + 'events?' + q.toString();
	}

	function loadEvents(select) {
		status('Loading events…', false);
		select.innerHTML = '<option value="">Loading…</option>';
		fetch(eventsQueryUrl(), {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': hlcData.nonce }
		})
			.then(function (r) {
				if (!r.ok) throw new Error('Could not load events.');
				return r.json();
			})
			.then(function (data) {
				eventsById    = {};
				eventsInOrder = [];
				select.innerHTML = '<option value="">— Select an event —</option>';
				var list = data.events || [];
				if (list.length === 0) {
					select.innerHTML = '<option value="">No past events in this year/month</option>';
					status('Try another month or year.', false);
					syncPreview();
					return;
				}
				list.forEach(function (ev) {
					eventsById[String(ev.id)] = ev;
					eventsInOrder.push(ev);
					var o = document.createElement('option');
					o.value = String(ev.id);
					o.textContent = ev.label + ' · ' + (ev.date_label || ev.start);
					select.appendChild(o);
				});
				status('', false);
				syncPreview();
			})
			.catch(function (e) {
				select.innerHTML = '<option value="">Error</option>';
				status(e.message || 'Failed to load events.', true);
			});
	}

	// Preload every image asset so src swaps in syncPreview are instant cache hits.
	function preloadMedia() {
		if (!hlcData || !hlcData.media) return;
		var urls = [
			hlcData.media.logoGw,
		hlcData.media.logoGm,
		hlcData.media.logoSub,
		hlcData.media.sealGw,
		hlcData.media.sealGm,
	];
		urls.forEach(function (url) {
			if (url) { (new Image()).src = url; }
		});
	}

	// Brief opacity blink so the user can see the preview refreshed even when content is identical.
	function flashPreview() {
		var card = el('hlc-cert-card');
		if (!card) return;
		card.classList.remove('hlc-refresh');
		// Force reflow so removing+re-adding the class restarts the animation.
		void card.offsetWidth;
		card.classList.add('hlc-refresh');
	}

	document.addEventListener('DOMContentLoaded', function () {
		var root = el('hlc-app');
		if (!root || typeof hlcData === 'undefined') return;

		preloadMedia();

		var select = el('hlc-event');
		var nameIn = el('hlc-participant');
		var agencyIn = el('hlc-agency');
		var emailIn = el('hlc-email');
		var btnDl = el('hlc-download');
		var btnMail = el('hlc-email-send');

		var classF = el('hlc-filter-class');
		var yearF = el('hlc-filter-year');
		var monthF = el('hlc-filter-month');

		loadEvents(select);
		syncPreview();

		if (classF) {
			classF.addEventListener('change', function () {
				syncPreview();           // immediately update preview text/logo/seal
				loadEvents(select);      // refresh dropdown to that class only
			});
		}
		if (yearF) yearF.addEventListener('change', function () { loadEvents(select); });
		if (monthF) monthF.addEventListener('change', function () { loadEvents(select); });

		select.addEventListener('change', syncPreview);
		if (nameIn) nameIn.addEventListener('input', syncPreview);
		if (agencyIn) agencyIn.addEventListener('input', syncPreview);

		function requestPdf(action) {
			var eventId = parseInt(select.value, 10);
			var participantName = (nameIn && nameIn.value) ? nameIn.value.trim() : '';
			var agency = (agencyIn && agencyIn.value) ? agencyIn.value.trim() : '';
			if (!eventId) {
				status('Please select an event.', true);
				return;
			}
			if (!participantName) {
				status('Please enter the participant name.', true);
				return;
			}

			var body = {
				event_id: eventId,
				participant_name: participantName,
				agency: agency,
				action: action
			};
			if (action === 'email') {
				var to = (emailIn && emailIn.value) ? emailIn.value.trim() : '';
				if (!to) {
					status('Please enter the recipient email.', true);
					return;
				}
				body.recipient_email = to;
			}

			status(action === 'email' ? 'Sending email…' : 'Building PDF…', false);
			if (btnDl) btnDl.disabled = true;
			if (btnMail) btnMail.disabled = true;

			fetch(hlcData.restUrl + 'pdf', {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': hlcData.nonce
				},
				body: JSON.stringify(body)
			})
				.then(function (r) {
					return r.json().then(function (j) {
						return { ok: r.ok, status: r.status, body: j };
					});
				})
				.then(function (res) {
					if (!res.ok) {
						throw new Error((res.body && res.body.message) || 'Request failed.');
					}
					if (action === 'email') {
						status(res.body.message || 'Sent.', false);
						return;
					}
					var b64 = res.body.pdf_base64;
					var filename = res.body.filename || 'certificate.pdf';
					if (!b64) throw new Error('Invalid PDF response.');
					var blob = base64ToBlob(b64, 'application/pdf');
					var url = URL.createObjectURL(blob);
					var a = document.createElement('a');
					a.href = url;
					a.download = filename;
					document.body.appendChild(a);
					a.click();
					a.remove();
					URL.revokeObjectURL(url);
					status('Download started.', false);
				})
				.catch(function (e) {
					status(e.message || 'Something went wrong.', true);
				})
				.finally(function () {
					if (btnDl) btnDl.disabled = false;
					if (btnMail) btnMail.disabled = false;
				});
		}

		if (btnDl) btnDl.addEventListener('click', function () { requestPdf('download'); });
		if (btnMail) btnMail.addEventListener('click', function () { requestPdf('email'); });
	});
})();
