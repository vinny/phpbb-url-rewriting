(function () {
	'use strict';

	function setServerType(select) {
		var apacheConfig = document.getElementById(select.getAttribute('data-apache-target'));
		var nginxConfig = document.getElementById(select.getAttribute('data-nginx-target'));
		var showNginx = select.value === 'nginx';

		if (!apacheConfig || !nginxConfig) {
			return;
		}

		apacheConfig.hidden = showNginx;
		nginxConfig.hidden = !showNginx;
	}

	function fallbackCopy(target) {
		target.focus();
		target.select();
		document.execCommand('copy');
	}

	document.addEventListener('DOMContentLoaded', function () {
		var serverTypes = document.querySelectorAll('.urlrewriting-server-select');
		var copyButtons = document.querySelectorAll('.urlrewriting-copy');

		for (var s = 0; s < serverTypes.length; s++) {
			setServerType(serverTypes[s]);
			serverTypes[s].addEventListener('change', function () {
				setServerType(this);
			});
		}

		for (var i = 0; i < copyButtons.length; i++) {
			copyButtons[i].addEventListener('click', function () {
				var target = document.getElementById(this.getAttribute('data-copy-target'));

				if (!target) {
					return;
				}

				if (navigator.clipboard && window.isSecureContext) {
					navigator.clipboard.writeText(target.value).catch(function () {
						fallbackCopy(target);
					});
				} else {
					fallbackCopy(target);
				}
			});
		}
	});
}());
