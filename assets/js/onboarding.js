/**
 * Stalkfish onboarding page.
 */
(function ($) {
	const onboardingParams = new URLSearchParams(window.location.search);
	$( document ).ready(function() {
		const step = onboardingParams.has('step') ? onboardingParams.get('step') : 1;
		$(`.sf-body #step-${step}`).show()
	});
})(jQuery)
