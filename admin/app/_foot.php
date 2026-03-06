</div><!-- /.wrap -->
</div><!-- /.ax-container -->
</main>
<div id="axToast" class="ax-toast" aria-live="polite" style="display:none"></div>
<script>
// Show a non-blocking toast message
function showToast(msg, timeout=2200) {
	const t = document.getElementById('axToast');
	if (!t) return;
	t.textContent = msg;
	t.style.display = 'block';
	requestAnimationFrame(() => t.classList.add('show'));
	clearTimeout(t._hideTimer);
	t._hideTimer = setTimeout(() => {
		t.classList.remove('show');
		setTimeout(() => t.style.display = 'none', 180);
	}, timeout);
}

// copy helper used across pages
function copyText(text) {
	if (navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(text).then(() => showToast('Copied: ' + text)).catch(() => showToast('Copy failed'));
	} else {
		try { prompt('Copy text:', text); } catch (e) { /* ignore */ }
	}
}

// Toggle simple action menu
function toggleMenu(id, btn) {
	const el = document.getElementById(id);
	if (!el) return;
	const shown = el.classList.contains('show');
	// hide others
	document.querySelectorAll('.action-menu').forEach(m => { if (m !== el) { m.classList.remove('show'); m.style.display = 'none'; } });
	if (shown) { el.classList.remove('show'); el.style.display = 'none'; if (btn) btn.setAttribute('aria-expanded','false'); return; }

	// Temporarily show to measure size (keeps visibility hidden to avoid flicker)
	el.style.display = 'block';
	el.style.visibility = 'hidden';
	el.style.position = 'fixed';
	el.style.left = '0px';
	el.style.top = '0px';
	const menuWidth = Math.max(180, el.offsetWidth || 180);
	const menuHeight = el.offsetHeight || 160;
	// measure button
	if (btn && btn.getBoundingClientRect) {
		const r = btn.getBoundingClientRect();
		const topBelow = r.bottom + 8;
		const topAbove = r.top - 8 - menuHeight;
		const preferredLeft = Math.min(window.innerWidth - 12 - menuWidth, Math.max(8, Math.round(r.right - menuWidth)));
		const top = (topBelow + menuHeight <= window.innerHeight - 8) ? topBelow : Math.max(8, topAbove);
		el.style.left = preferredLeft + 'px';
		el.style.top = top + 'px';
		el.style.minWidth = menuWidth + 'px';
		el.style.zIndex = 9999;
	}
	// reveal
	el.style.visibility = '';
	requestAnimationFrame(() => { el.classList.add('show'); if (btn) btn.setAttribute('aria-expanded','true'); });
}

// Close menus when clicking outside
document.addEventListener('click', function(e){
	if (e.target.closest('.action-menu') || e.target.closest('.kebab-btn')) return;
	document.querySelectorAll('.action-menu').forEach(m => m.style.display = 'none');
});

// Close menus with Escape and maintain focus/expanded state
document.addEventListener('keydown', function(e){
	if (e.key === 'Escape') {
		document.querySelectorAll('.action-menu').forEach(m => { m.classList.remove('show'); m.style.display = 'none'; });
		document.querySelectorAll('.kebab-btn[aria-expanded="true"]').forEach(b => b.setAttribute('aria-expanded','false'));
	}
});
</script>
</body>
</html>
