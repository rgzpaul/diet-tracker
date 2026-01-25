<?php
// Load the daily meals database
$dailyMealsFile = 'data/daily_meals.json';

if (!file_exists($dailyMealsFile)) {
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
    $weeklySummary['avgKcal'] = round($weeklySummary['totalKcal'] / $weeklySummary['daysTracked']);
    $weeklySummary['avgProtein'] = round($weeklySummary['totalProtein'] / $weeklySummary['daysTracked'], 1);
    $weeklySummary['avgCarbs'] = round($weeklySummary['totalCarbs'] / $weeklySummary['daysTracked'], 1);
    $weeklySummary['avgFat'] = round($weeklySummary['totalFat'] / $weeklySummary['daysTracked'], 1);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Report - Meal Tracker</title>
    <link rel="manifest" href="./manifest.json">
    <meta name="theme-color" content="#57534e">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
        <p class="text-center mb-6 text-stone-500 text-sm"><?php echo $weekStartDisplay; ?> - <?php echo $weekEndDisplay; ?></p>

        <!-- Week Navigation -->
        <div class="flex flex-wrap justify-center items-center mb-6 gap-2">
            <a href="?week=<?php echo $weekOffset - 1; ?>" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-600 rounded-lg text-sm transition-colors">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                Previous
            </a>

            <?php if ($weekOffset < 0): ?>
                <a href="?week=<?php echo $weekOffset + 1; ?>" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-600 rounded-lg text-sm transition-colors">
                    Next
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            <?php endif; ?>

            <?php if ($weekOffset != 0): ?>
                <a href="?week=0" class="inline-flex items-center gap-1.5 px-3 py-2 bg-stone-800 hover:bg-stone-900 text-white rounded-lg text-sm transition-colors">
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
                    <div class="text-xl font-semibold text-stone-800"><?php echo $weeklySummary['avgKcal']; ?></div>
                </div>
                <div class="bg-stone-50 p-4 rounded-lg text-center">
                    <div class="text-xs text-stone-500 uppercase tracking-wide mb-1">Avg Protein</div>
                    <div class="text-xl font-semibold text-stone-800"><?php echo $weeklySummary['avgProtein']; ?>g</div>
                </div>
                <div class="bg-stone-50 p-4 rounded-lg text-center">
                    <div class="text-xs text-stone-500 uppercase tracking-wide mb-1">Avg Carbs</div>
                    <div class="text-xl font-semibold text-stone-800"><?php echo $weeklySummary['avgCarbs']; ?>g</div>
                </div>
                <div class="bg-stone-50 p-4 rounded-lg text-center">
                    <div class="text-xs text-stone-500 uppercase tracking-wide mb-1">Avg Fat</div>
                    <div class="text-xl font-semibold text-stone-800"><?php echo $weeklySummary['avgFat']; ?>g</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="bg-stone-50 p-3 rounded-lg flex items-center gap-3">
                    <i data-lucide="calendar-check" class="w-5 h-5 text-stone-500"></i>
                    <div>
                        <div class="text-xs text-stone-500">Days Tracked</div>
                        <div class="font-semibold text-stone-800"><?php echo $weeklySummary['daysTracked']; ?> / 7</div>
                    </div>
                </div>
                <div class="bg-stone-50 p-3 rounded-lg flex items-center gap-3">
                    <i data-lucide="flame" class="w-5 h-5 text-stone-500"></i>
                    <div>
                        <div class="text-xs text-stone-500">Total Calories</div>
                        <div class="font-semibold text-stone-800"><?php echo number_format($weeklySummary['totalKcal']); ?></div>
                    </div>
                </div>
                <div class="bg-stone-50 p-3 rounded-lg flex items-center gap-3">
                    <i data-lucide="beef" class="w-5 h-5 text-stone-500"></i>
                    <div>
                        <div class="text-xs text-stone-500">Total Protein</div>
                        <div class="font-semibold text-stone-800"><?php echo $weeklySummary['totalProtein']; ?>g</div>
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
                            <th class="pb-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wide">Day</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide">K</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide">P</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide">C</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide">F</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weekDays as $day): ?>
                            <tr class="<?php echo empty($day['meals']) ? 'text-stone-400' : 'hover:bg-stone-50'; ?> border-b border-stone-100 transition-colors">
                                <td class="py-3 font-medium <?php echo empty($day['meals']) ? 'text-stone-400' : 'text-stone-700'; ?>"><?php echo strtoupper($day['date']); ?></td>
                                <td class="py-3 text-center <?php echo empty($day['meals']) ? '' : 'font-medium text-stone-600'; ?>"><?php echo empty($day['meals']) ? '-' : $day['totals']['kcal']; ?></td>
                                <td class="py-3 text-center text-stone-500"><?php echo empty($day['meals']) ? '-' : $day['totals']['protein']; ?></td>
                                <td class="py-3 text-center text-stone-500"><?php echo empty($day['meals']) ? '-' : $day['totals']['carbs']; ?></td>
                                <td class="py-3 text-center text-stone-500"><?php echo empty($day['meals']) ? '-' : $day['totals']['fat']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detailed View -->
        <?php if ($weeklySummary['daysTracked'] > 0): ?>
            <div class="bg-white rounded-xl border border-stone-200 p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <i data-lucide="list" class="w-5 h-5 text-stone-500"></i>
                    <h2 class="text-lg font-medium text-stone-700">Detailed View</h2>
                </div>

                <div class="space-y-2">
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
                                <div id="day-<?php echo $index; ?>" class="p-4 day-content hidden border-t border-stone-100">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-xs text-stone-500 uppercase tracking-wide">
                                                <th class="pb-2 text-left">Meal</th>
                                                <th class="pb-2 text-center">K</th>
                                                <th class="pb-2 text-center">P</th>
                                                <th class="pb-2 text-center">C</th>
                                                <th class="pb-2 text-center">F</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($day['meals'] as $meal): ?>
                                                <?php $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']); ?>
                                                <tr class="border-t border-stone-100">
                                                    <td class="py-2 text-stone-700"><?php echo htmlspecialchars($meal['name']); ?></td>
                                                    <td class="py-2 text-center text-stone-600"><?php echo $mealKcal; ?></td>
                                                    <td class="py-2 text-center text-stone-500"><?php echo $meal['protein']; ?></td>
                                                    <td class="py-2 text-center text-stone-500"><?php echo $meal['carbs']; ?></td>
                                                    <td class="py-2 text-center text-stone-500"><?php echo $meal['fat']; ?></td>
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
        <?php endif; ?>

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
            // Toggle detailed day view
            $('.day-header').on('click', function() {
                const target = $(this).data('target');
                const content = $('#' + target);
                const icon = $(this).find('.toggle-icon');

                content.toggleClass('hidden');
                icon.toggleClass('rotate-180');
            });
        });
    </script>
    <script src="sort.js"></script>
</body>

</html>
