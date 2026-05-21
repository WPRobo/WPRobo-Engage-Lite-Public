(function ($) {
	'use strict';

	$(function () {
		// Only run on dashboard page
		if (!$('#wpr-analytics-chart').length) {
			return;
		}

		// Fetch analytics data from REST API
		$.ajax({
			url: WPRoboDashboard.apiUrl,
			method: 'GET',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', WPRoboDashboard.nonce);
			},
			success: function (response) {
				renderChart(response);
			},
			error: function (xhr, status, error) {
				console.error('Failed to load analytics data:', error);
				$('#wpr-analytics-chart').html('<p class="wpr-text-center wpr-text-red-500 wpr-py-8">' + WPRoboDashboard.errorMessage + '</p>');
			}
		});

		function renderChart(data) {
			var ctx = document.getElementById('wpr-analytics-chart').getContext('2d');

			// Format dates for display (show month/day only)
			var formattedLabels = data.labels.map(function(dateStr) {
				var date = new Date(dateStr + 'T00:00:00');
				return (date.getMonth() + 1) + '/' + date.getDate();
			});

			new Chart(ctx, {
				type: 'line',
				data: {
					labels: formattedLabels,
					datasets: [
						{
							label: WPRoboDashboard.impressionsLabel,
							data: data.impressions,
							borderColor: 'rgb(59, 130, 246)',
							backgroundColor: 'rgba(59, 130, 246, 0.1)',
							borderWidth: 2,
							tension: 0.4,
							fill: true,
							pointRadius: 3,
							pointHoverRadius: 5
						},
						{
							label: WPRoboDashboard.conversionsLabel,
							data: data.conversions,
							borderColor: 'rgb(34, 197, 94)',
							backgroundColor: 'rgba(34, 197, 94, 0.1)',
							borderWidth: 2,
							tension: 0.4,
							fill: true,
							pointRadius: 3,
							pointHoverRadius: 5
						}
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					interaction: {
						mode: 'index',
						intersect: false
					},
					plugins: {
						legend: {
							display: true,
							position: 'top'
						},
						tooltip: {
							position: 'nearest',
							yAlign: 'bottom',
							caretPadding: 10,
							backgroundColor: '#1e293b',
							titleColor: '#f8fafc',
							bodyColor: '#e2e8f0',
							borderColor: '#334155',
							borderWidth: 1,
							cornerRadius: 6,
							padding: { x: 12, y: 10 },
							titleFont: { size: 13, weight: '600' },
							bodyFont: { size: 13 },
							displayColors: true,
							boxPadding: 4
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: { precision: 0 },
							grid: { color: 'rgba(0, 0, 0, 0.05)' }
						},
						x: {
							grid: { display: false }
						}
					}
				}
			});
		}
	});
})(jQuery);
