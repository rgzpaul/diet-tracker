<?php
// Load the daily meals database
$dailyMealsFile = 'data/daily_meals.json';

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!file_exists($dailyMealsFile)) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No data available']);
        exit;
    }
    echo "No data available. Please add some meals first.";
    exit;
}

$dailyMeals = json_decode(file_get_contents($dailyMealsFile), true);

// Function to calculate kcal based on macros
function calculateKcal($protein, $carbs, $fat)
{
    return ($protein * 4) + ($carbs * 4) + ($fat * 9);
}

// Handle week navigation
$weekOffset = isset($_GET['week']) ? (int)$_GET['week'] : 0;

// Calculate the start and end dates for the selected week
$today = new DateTime();
$weekStart = clone $today;

// Get to current week's Monday
$dayOfWeek = $weekStart->format('N'); // 1 (Monday) through, 7 (Sunday)
if ($dayOfWeek == 1) {
    // If today is Monday, start with today
    // No need to modify
} else {
    // Otherwise go back to this week's Monday
    $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
}

// Apply week offset
if ($weekOffset != 0) {
    $weekStart->modify("$weekOffset week");
}
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');

// Format dates for display and for array keys
$weekStartDisplay = $weekStart->format('d/m/Y');
$weekEndDisplay = $weekEnd->format('d/m/Y');

// Initialize the weekly data
$weekDays = [];
$weeklySummary = [
    'totalKcal' => 0,
    'totalProtein' => 0,
    'totalCarbs' => 0,
    'totalFat' => 0,
    'avgKcal' => 0,
    'avgProtein' => 0,
    'avgCarbs' => 0,
    'avgFat' => 0,
    'daysTracked' => 0
];

// Loop through each day of the week
for ($i = 0; $i < 7; $i++) {
    $currentDay = clone $weekStart;
    $currentDay->modify("+$i days");
    $dateKey = $currentDay->format('Y-m-d');
    $displayKey = $currentDay->format('D d/m');

    $dayData = [
        'date' => $displayKey,
        'meals' => [],
        'totals' => [
            'kcal' => 0,
            'protein' => 0,
            'carbs' => 0,
            'fat' => 0
        ]
    ];

    // If we have data for this day, add it
    if (isset($dailyMeals[$dateKey])) {
        $dayData['meals'] = $dailyMeals[$dateKey]['meals'];

        // Calculate totals for this day
        foreach ($dayData['meals'] as $meal) {
            $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']);
            $dayData['totals']['kcal'] += $mealKcal;
            $dayData['totals']['protein'] += $meal['protein'];
            $dayData['totals']['carbs'] += $meal['carbs'];
            $dayData['totals']['fat'] += $meal['fat'];
        }

        // Round day totals to 2 decimal places
        $dayData['totals']['kcal'] = round($dayData['totals']['kcal'], 2);
        $dayData['totals']['protein'] = round($dayData['totals']['protein'], 2);
        $dayData['totals']['carbs'] = round($dayData['totals']['carbs'], 2);
        $dayData['totals']['fat'] = round($dayData['totals']['fat'], 2);

        // Add to weekly totals
        $weeklySummary['totalKcal'] += $dayData['totals']['kcal'];
        $weeklySummary['totalProtein'] += $dayData['totals']['protein'];
        $weeklySummary['totalCarbs'] += $dayData['totals']['carbs'];
        $weeklySummary['totalFat'] += $dayData['totals']['fat'];
        $weeklySummary['daysTracked']++;
    }

    $weekDays[] = $dayData;
}

// Calculate averages (only for days that have data)
if ($weeklySummary['daysTracked'] > 0) {
    $weeklySummary['avgKcal'] = round($weeklySummary['totalKcal'] / $weeklySummary['daysTracked'], 2);
    $weeklySummary['avgProtein'] = round($weeklySummary['totalProtein'] / $weeklySummary['daysTracked'], 2);
    $weeklySummary['avgCarbs'] = round($weeklySummary['totalCarbs'] / $weeklySummary['daysTracked'], 2);
    $weeklySummary['avgFat'] = round($weeklySummary['totalFat'] / $weeklySummary['daysTracked'], 2);
}

// Round weekly totals
$weeklySummary['totalKcal'] = round($weeklySummary['totalKcal'], 2);
$weeklySummary['totalProtein'] = round($weeklySummary['totalProtein'], 2);
$weeklySummary['totalCarbs'] = round($weeklySummary['totalCarbs'], 2);
$weeklySummary['totalFat'] = round($weeklySummary['totalFat'], 2);

// Return JSON for AJAX requests
if ($isAjax) {
    // Add kcal to each meal in weekDays and round values
    foreach ($weekDays as &$day) {
        foreach ($day['meals'] as &$meal) {
            $meal['kcal'] = round(calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']), 2);
            $meal['protein'] = round($meal['protein'], 2);
            $meal['carbs'] = round($meal['carbs'], 2);
            $meal['fat'] = round($meal['fat'], 2);
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'weekOffset' => $weekOffset,
        'weekStartDisplay' => $weekStartDisplay,
        'weekEndDisplay' => $weekEndDisplay,
        'weeklySummary' => $weeklySummary,
        'weekDays' => $weekDays
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="#57534e">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        th, td {
            padding: 6px 10px;
            white-space: nowrap;
        }
    </style>
</head>

<body class="bg-stone-100 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex items-center justify-center gap-3 mb-2">
            <i data-lucide="bar-chart-2" class="w-7 h-7 text-stone-700"></i>
            <h1 class="text-2xl font-semibold text-stone-800">Weekly Report</h1>
        </div>
        <p class="text-center mb-6 text-stone-500 text-sm week-date-range"><?php echo $weekStartDisplay; ?> - <?php echo $weekEndDisplay; ?></p>

        <!-- Week Navigation -->
        <div id="week-nav-container" class="flex flex-wrap justify-center items-center mb-6 gap-2">
            <a href="?week=<?php echo $weekOffset - 1; ?>" id="prev-week-btn" class="week-nav-btn inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-600 rounded-lg text-sm transition-colors">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                Previous
            </a>

            <?php if ($weekOffset < 0): ?>
                <a href="?week=<?php echo $weekOffset + 1; ?>" id="next-week-btn" class="week-nav-btn inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-600 rounded-lg text-sm transition-colors">
                    Next
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            <?php endif; ?>

            <?php if ($weekOffset != 0): ?>
                <a href="?week=0" id="current-week-btn" class="week-nav-btn inline-flex items-center gap-1.5 px-3 py-2 bg-stone-800 hover:bg-stone-900 text-white rounded-lg text-sm transition-colors">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    Current
                </a>
            <?php endif; ?>
        </div>

        <!-- Weekly Summary -->
        <div class="bg-white rounded-xl border border-stone-200 p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="activity" class="w-5 h-5 text-stone-500"></i>
                <h2 class="text-lg font-medium text-stone-700">Summary</h2>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-stone-50 p-4 rounded-lg text-center">
                    <div class="text-xs text-stone-500 uppercase tracking-wide mb-1">Avg Calories</div>
                    <div id="avg-kcal" class="text-xl font-semibold text-stone-800"><?php echo $weeklySummary['avgKcal']; ?></div>
                </div>
                <div class="bg-stone-50 p-4 rounded-lg text-center">
                    <div class="text-xs text-stone-500 uppercase tracking-wide mb-1">Avg Protein</div>
                    <div id="avg-protein" class="text-xl font-semibold text-stone-800"><?php echo $weeklySummary['avgProtein']; ?>g</div>
                </div>
                <div class="bg-stone-50 p-4 rounded-lg text-center">
                    <div class="text-xs text-stone-500 uppercase tracking-wide mb-1">Avg Carbs</div>
                    <div id="avg-carbs" class="text-xl font-semibold text-stone-800"><?php echo $weeklySummary['avgCarbs']; ?>g</div>
                </div>
                <div class="bg-stone-50 p-4 rounded-lg text-center">
                    <div class="text-xs text-stone-500 uppercase tracking-wide mb-1">Avg Fat</div>
                    <div id="avg-fat" class="text-xl font-semibold text-stone-800"><?php echo $weeklySummary['avgFat']; ?>g</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="bg-stone-50 p-3 rounded-lg flex items-center gap-3">
                    <div class="p-2 bg-stone-100 rounded-lg">
                        <i data-lucide="calendar-check" class="w-5 h-5 text-stone-500"></i>
                    </div>
                    <div>
                        <div class="text-xs text-stone-500">Days Tracked</div>
                        <div id="days-tracked" class="font-semibold text-stone-800"><?php echo $weeklySummary['daysTracked']; ?> / 7</div>
                    </div>
                </div>
                <div class="bg-stone-50 p-3 rounded-lg flex items-center gap-3">
                    <div class="p-2 bg-stone-100 rounded-lg">
                        <i data-lucide="flame" class="w-5 h-5 text-stone-500"></i>
                    </div>
                    <div>
                        <div class="text-xs text-stone-500">Total Calories</div>
                        <div id="total-kcal" class="font-semibold text-stone-800"><?php echo number_format($weeklySummary['totalKcal']); ?></div>
                    </div>
                </div>
                <div class="bg-stone-50 p-3 rounded-lg flex items-center gap-3">
                    <div class="p-2 bg-stone-100 rounded-lg">
                        <i data-lucide="beef" class="w-5 h-5 text-stone-500"></i>
                    </div>
                    <div>
                        <div class="text-xs text-stone-500">Total Protein</div>
                        <div id="total-protein" class="font-semibold text-stone-800"><?php echo $weeklySummary['totalProtein']; ?>g</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Breakdown -->
        <div class="bg-white rounded-xl border border-stone-200 p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="table" class="w-5 h-5 text-stone-500"></i>
                <h2 class="text-lg font-medium text-stone-700">Daily Breakdown</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-stone-200">
                            <th class="text-left text-xs font-medium text-stone-500 uppercase tracking-wide">Day</th>
                            <th class="text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">K</th>
                            <th class="text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">P</th>
                            <th class="text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">C</th>
                            <th class="text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">F</th>
                        </tr>
                    </thead>
                    <tbody id="daily-breakdown-tbody">
                        <?php foreach ($weekDays as $day): ?>
                            <tr class="<?php echo empty($day['meals']) ? 'text-stone-400' : 'hover:bg-stone-50'; ?> border-b border-stone-100 transition-colors">
                                <td class=font-medium <?php echo empty($day['meals']) ? 'text-stone-400' : 'text-stone-700'; ?>"><?php echo strtoupper($day['date']); ?></td>
                                <td class=text-center border-l border-stone-100 <?php echo empty($day['meals']) ? '' : 'font-medium text-stone-600'; ?>"><?php echo empty($day['meals']) ? '-' : $day['totals']['kcal']; ?></td>
                                <td class=text-center text-stone-500 border-l border-stone-100"><?php echo empty($day['meals']) ? '-' : $day['totals']['protein']; ?></td>
                                <td class=text-center text-stone-500 border-l border-stone-100"><?php echo empty($day['meals']) ? '-' : $day['totals']['carbs']; ?></td>
                                <td class=text-center text-stone-500 border-l border-stone-100"><?php echo empty($day['meals']) ? '-' : $day['totals']['fat']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detailed View -->
        <div id="detailed-view-section" class="<?php echo $weeklySummary['daysTracked'] > 0 ? '' : 'hidden'; ?>">
            <div class="bg-white rounded-xl border border-stone-200 p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <i data-lucide="list" class="w-5 h-5 text-stone-500"></i>
                    <h2 class="text-lg font-medium text-stone-700">Detailed View</h2>
                </div>

                <div id="detailed-view-container" class="space-y-2">
                    <?php foreach ($weekDays as $index => $day): ?>
                        <?php if (!empty($day['meals'])): ?>
                            <div class="border border-stone-200 rounded-lg overflow-hidden">
                                <div class="bg-stone-50 px-4 py-3 cursor-pointer day-header flex justify-between items-center" data-target="day-<?php echo $index; ?>">
                                    <div class="font-medium text-stone-700"><?php echo strtoupper($day['date']); ?></div>
                                    <div class="flex items-center gap-2 text-sm text-stone-500">
                                        <?php echo $day['totals']['kcal']; ?> kcal
                                        <i data-lucide="chevron-down" class="w-4 h-4 toggle-icon transition-transform"></i>
                                    </div>
                                </div>
                                <div id="day-<?php echo $index; ?>" class=" p-4 overflow-x-auto day-content hidden border-t border-stone-100">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-xs text-stone-500 uppercase tracking-wide">
                                                <th class="text-left">Meal</th>
                                                <th class="text-center border-l border-stone-100">K</th>
                                                <th class="text-center border-l border-stone-100">P</th>
                                                <th class="text-center border-l border-stone-100">C</th>
                                                <th class="text-center border-l border-stone-100">F</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($day['meals'] as $meal): ?>
                                                <?php $mealKcal = round(calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']), 2); ?>
                                                <tr class="border-t border-stone-100">
                                                    <td class=text-stone-700"><?php echo htmlspecialchars($meal['name']); ?></td>
                                                    <td class=text-center text-stone-600 border-l border-stone-100"><?php echo $mealKcal; ?></td>
                                                    <td class=text-center text-stone-500 border-l border-stone-100"><?php echo round($meal['protein'], 2); ?></td>
                                                    <td class=text-center text-stone-500 border-l border-stone-100"><?php echo round($meal['carbs'], 2); ?></td>
                                                    <td class=text-center text-stone-500 border-l border-stone-100"><?php echo round($meal['fat'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex justify-center gap-3">
            <a href="index.php" class="inline-flex items-center gap-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 font-medium py-2.5 px-4 rounded-lg transition-colors">
                <i data-lucide="utensils" class="w-4 h-4"></i>
                Tracker
            </a>
            <a href="create.php" class="inline-flex items-center gap-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 font-medium py-2.5 px-4 rounded-lg transition-colors">
                <i data-lucide="settings" class="w-4 h-4"></i>
                Manage
            </a>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        $(document).ready(function() {
            let currentWeekOffset = <?php echo $weekOffset; ?>;

            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Function to bind day header toggle
            function bindDayHeaders() {
                $('.day-header').off('click').on('click', function() {
                    const target = $(this).data('target');
                    const content = $('#' + target);
                    const icon = $(this).find('.toggle-icon');

                    content.toggleClass('hidden');
                    icon.toggleClass('rotate-180');
                });
            }

            // Initial binding
            bindDayHeaders();

            // Function to load week data via AJAX
            function loadWeek(weekOffset) {
                $('.week-nav-btn').addClass('opacity-50').prop('disabled', true);

                $.ajax({
                    url: 'report.php',
                    method: 'GET',
                    data: { week: weekOffset },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            currentWeekOffset = response.weekOffset;

                            // Update date range display
                            $('.week-date-range').text(response.weekStartDisplay + ' - ' + response.weekEndDisplay);

                            // Update URL without reload
                            const newUrl = weekOffset === 0 ? 'report.php' : 'report.php?week=' + weekOffset;
                            history.pushState({ week: weekOffset }, '', newUrl);

                            // Update summary stats
                            updateSummary(response.weeklySummary);

                            // Update daily breakdown table
                            updateDailyBreakdown(response.weekDays);

                            // Update detailed view
                            updateDetailedView(response.weekDays);

                            // Update navigation buttons
                            updateNavButtons(weekOffset);

                            // Reinitialize icons
                            lucide.createIcons();

                            // Rebind day headers
                            bindDayHeaders();
                        }
                        $('.week-nav-btn').removeClass('opacity-50').prop('disabled', false);
                    },
                    error: function() {
                        alert('Error loading week data. Please try again.');
                        $('.week-nav-btn').removeClass('opacity-50').prop('disabled', false);
                    }
                });
            }

            // Helper function to format number to max 2 decimal places
            function formatNumber(num) {
                return parseFloat(num.toFixed(2));
            }

            // Update summary section
            function updateSummary(summary) {
                $('#avg-kcal').text(formatNumber(summary.avgKcal));
                $('#avg-protein').text(formatNumber(summary.avgProtein) + 'g');
                $('#avg-carbs').text(formatNumber(summary.avgCarbs) + 'g');
                $('#avg-fat').text(formatNumber(summary.avgFat) + 'g');
                $('#days-tracked').text(summary.daysTracked + ' / 7');
                $('#total-kcal').text(formatNumber(summary.totalKcal).toLocaleString());
                $('#total-protein').text(formatNumber(summary.totalProtein) + 'g');
            }

            // Update daily breakdown table
            function updateDailyBreakdown(weekDays) {
                let html = '';
                weekDays.forEach(function(day) {
                    const isEmpty = day.meals.length === 0;
                    const rowClass = isEmpty ? 'text-stone-400' : 'hover:bg-stone-50';
                    const dateClass = isEmpty ? 'text-stone-400' : 'text-stone-700';

                    html += `
                        <tr class="${rowClass} border-b border-stone-100 transition-colors">
                            <td class=font-medium ${dateClass}">${day.date.toUpperCase()}</td>
                            <td class=text-center border-l border-stone-100 ${isEmpty ? '' : 'font-medium text-stone-600'}">${isEmpty ? '-' : formatNumber(day.totals.kcal)}</td>
                            <td class=text-center text-stone-500 border-l border-stone-100">${isEmpty ? '-' : formatNumber(day.totals.protein)}</td>
                            <td class=text-center text-stone-500 border-l border-stone-100">${isEmpty ? '-' : formatNumber(day.totals.carbs)}</td>
                            <td class=text-center text-stone-500 border-l border-stone-100">${isEmpty ? '-' : formatNumber(day.totals.fat)}</td>
                        </tr>
                    `;
                });
                $('#daily-breakdown-tbody').html(html);
            }

            // Update detailed view section
            function updateDetailedView(weekDays) {
                let html = '';
                let dayIndex = 0;

                weekDays.forEach(function(day) {
                    if (day.meals.length > 0) {
                        let mealsHtml = '';
                        day.meals.forEach(function(meal) {
                            mealsHtml += `
                                <tr class="border-t border-stone-100">
                                    <td class=text-stone-700">${escapeHtml(meal.name)}</td>
                                    <td class=text-center text-stone-600 border-l border-stone-100">${formatNumber(meal.kcal)}</td>
                                    <td class=text-center text-stone-500 border-l border-stone-100">${formatNumber(meal.protein)}</td>
                                    <td class=text-center text-stone-500 border-l border-stone-100">${formatNumber(meal.carbs)}</td>
                                    <td class=text-center text-stone-500 border-l border-stone-100">${formatNumber(meal.fat)}</td>
                                </tr>
                            `;
                        });

                        html += `
                            <div class="border border-stone-200 rounded-lg overflow-hidden">
                                <div class="bg-stone-50 px-4 py-3 cursor-pointer day-header flex justify-between items-center" data-target="day-${dayIndex}">
                                    <div class="font-medium text-stone-700">${day.date.toUpperCase()}</div>
                                    <div class="flex items-center gap-2 text-sm text-stone-500">
                                        ${formatNumber(day.totals.kcal)} kcal
                                        <i data-lucide="chevron-down" class="w-4 h-4 toggle-icon transition-transform"></i>
                                    </div>
                                </div>
                                <div id="day-${dayIndex}" class=" p-4 overflow-x-auto day-content hidden border-t border-stone-100">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-xs text-stone-500 uppercase tracking-wide">
                                                <th class="text-left">Meal</th>
                                                <th class="text-center border-l border-stone-100">K</th>
                                                <th class="text-center border-l border-stone-100">P</th>
                                                <th class="text-center border-l border-stone-100">C</th>
                                                <th class="text-center border-l border-stone-100">F</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${mealsHtml}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                    }
                    dayIndex++;
                });

                const $container = $('#detailed-view-container');
                if (html) {
                    $container.html('<div class="space-y-2">' + html + '</div>');
                    $('#detailed-view-section').removeClass('hidden');
                } else {
                    $('#detailed-view-section').addClass('hidden');
                }
            }

            // Update navigation buttons
            function updateNavButtons(weekOffset) {
                // Update Previous button href
                $('#prev-week-btn').attr('href', '?week=' + (weekOffset - 1));

                // Show/hide Next button
                if (weekOffset < 0) {
                    const nextHtml = `
                        <a href="?week=${weekOffset + 1}" id="next-week-btn" class="week-nav-btn inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-600 rounded-lg text-sm transition-colors">
                            Next
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </a>
                    `;
                    if (!$('#next-week-btn').length) {
                        $('#prev-week-btn').after(nextHtml);
                    } else {
                        $('#next-week-btn').attr('href', '?week=' + (weekOffset + 1));
                    }
                } else {
                    $('#next-week-btn').remove();
                }

                // Show/hide Current button
                if (weekOffset !== 0) {
                    if (!$('#current-week-btn').length) {
                        const currentHtml = `
                            <a href="?week=0" id="current-week-btn" class="week-nav-btn inline-flex items-center gap-1.5 px-3 py-2 bg-stone-800 hover:bg-stone-900 text-white rounded-lg text-sm transition-colors">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                                Current
                            </a>
                        `;
                        $('#week-nav-container').append(currentHtml);
                    }
                } else {
                    $('#current-week-btn').remove();
                }
            }

            // Handle navigation clicks with AJAX
            $(document).on('click', '.week-nav-btn', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                const weekParam = new URLSearchParams(href.split('?')[1] || '');
                const weekOffset = parseInt(weekParam.get('week')) || 0;
                loadWeek(weekOffset);
            });

            // Handle browser back/forward
            $(window).on('popstate', function(e) {
                const state = e.originalEvent.state;
                if (state && typeof state.week !== 'undefined') {
                    loadWeek(state.week);
                } else {
                    loadWeek(0);
                }
            });
        });
    </script>
    <script src="sort.js"></script>
</body>

</html>
