<?php
// Load the daily meals database
$dailyMealsFile = 'data/daily_meals.json';

if (!file_exists($dailyMealsFile)) {
    echo "No data available. Please add some meals first.";
    exit;
}

$dailyMeals = json_decode(file_get_contents($dailyMealsFile), true);

// Function to calculate kcal based on macros
function calculateKcal($protein, $carbs, $fat) {
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-2 text-green-700">Weekly Meal Report</h1>
        <p class="text-center mb-4 text-gray-600"><?php echo $weekStartDisplay; ?> - <?php echo $weekEndDisplay; ?></p>
        
        <!-- Week Navigation -->
        <div class="flex flex-wrap justify-center items-center mb-8 gap-2">
            <a href="?week=<?php echo $weekOffset - 1; ?>" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Previous Week
            </a>
            
            <?php if ($weekOffset < 0): ?>
            <a href="?week=<?php echo $weekOffset + 1; ?>" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg flex items-center">
                Next Week
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <?php endif; ?>
            
            <?php if ($weekOffset != 0): ?>
            <a href="?week=0" class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-lg">
                Current Week
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Weekly Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Weekly Summary</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 p-4 rounded-lg text-center">
                    <div class="text-sm text-gray-600">Avg. Daily Calories</div>
                    <div class="text-2xl font-bold text-green-700"><?php echo $weeklySummary['avgKcal']; ?> kcal</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg text-center">
                    <div class="text-sm text-gray-600">Avg. Daily Protein</div>
                    <div class="text-2xl font-bold text-blue-700"><?php echo $weeklySummary['avgProtein']; ?> g</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg text-center">
                    <div class="text-sm text-gray-600">Avg. Daily Carbs</div>
                    <div class="text-2xl font-bold text-yellow-700"><?php echo $weeklySummary['avgCarbs']; ?> g</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg text-center">
                    <div class="text-sm text-gray-600">Avg. Daily Fat</div>
                    <div class="text-2xl font-bold text-red-700"><?php echo $weeklySummary['avgFat']; ?> g</div>
                </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg text-center mb-4">
                <div class="text-sm text-gray-600">Days Tracked</div>
                <div class="text-xl font-semibold"><?php echo $weeklySummary['daysTracked']; ?> of 7 days</div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600 mb-1">Total Weekly Calories</div>
                    <div class="text-xl font-semibold"><?php echo number_format($weeklySummary['totalKcal']); ?> kcal</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600 mb-1">Total Weekly Macros</div>
                    <div class="text-lg">
                        Protein: <span class="font-semibold"><?php echo $weeklySummary['totalProtein']; ?> g</span> | 
                        Carbs: <span class="font-semibold"><?php echo $weeklySummary['totalCarbs']; ?> g</span> | 
                        Fat: <span class="font-semibold"><?php echo $weeklySummary['totalFat']; ?> g</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Daily Breakdown -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Daily Breakdown</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2 text-left">Day</th>
                            <th class="border p-2 text-center">K</th>
                            <th class="border p-2 text-center">P (g)</th>
                            <th class="border p-2 text-center">C (g)</th>
                            <th class="border p-2 text-center">F (g)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weekDays as $day): ?>
                            <tr class="<?php echo empty($day['meals']) ? 'bg-gray-50 text-gray-500' : 'hover:bg-gray-50'; ?>">
                                <td class="border p-2 font-medium"><?php echo strtoupper($day['date']); ?></td>
                                <td class="border p-2 text-center"><?php echo empty($day['meals']) ? '-' : $day['totals']['kcal']; ?></td>
                                <td class="border p-2 text-center"><?php echo empty($day['meals']) ? '-' : $day['totals']['protein']; ?></td>
                                <td class="border p-2 text-center"><?php echo empty($day['meals']) ? '-' : $day['totals']['carbs']; ?></td>
                                <td class="border p-2 text-center"><?php echo empty($day['meals']) ? '-' : $day['totals']['fat']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Detailed View (Optional, expandable sections) -->
        <?php if ($weeklySummary['daysTracked'] > 0): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Detailed Daily View</h2>
            
            <div class="space-y-4">
                <?php foreach ($weekDays as $index => $day): ?>
                    <?php if (!empty($day['meals'])): ?>
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-100 p-3 cursor-pointer day-header" data-target="day-<?php echo $index; ?>">
                                <div class="flex justify-between items-center">
                                    <div class="font-medium"><?php echo strtoupper($day['date']); ?></div>
                                    <div>
                                        <?php echo $day['totals']['kcal']; ?> kcal
                                        <span class="ml-1 text-gray-500">▼</span>
                                    </div>
                                </div>
                            </div>
                            <div id="day-<?php echo $index; ?>" class="p-3 day-content hidden">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-sm text-gray-600">
                                            <th class="p-1 text-left">Meal</th>
                                            <th class="p-1 text-center">K</th>
                                            <th class="p-1 text-center">P</th>
                                            <th class="p-1 text-center">C</th>
                                            <th class="p-1 text-center">F</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($day['meals'] as $meal): ?>
                                            <?php $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']); ?>
                                            <tr class="border-t">
                                                <td class="p-1"><?php echo htmlspecialchars($meal['name']); ?></td>
                                                <td class="p-1 text-center"><?php echo $mealKcal; ?></td>
                                                <td class="p-1 text-center"><?php echo $meal['protein']; ?></td>
                                                <td class="p-1 text-center"><?php echo $meal['carbs']; ?></td>
                                                <td class="p-1 text-center"><?php echo $meal['fat']; ?></td>
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
        <div class="text-center">
            <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 mx-2 rounded-lg text-center">
                Daily Tracker
            </a>
            <a href="create.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 mx-2 rounded-lg text-center">
                Manage Meals
            </a>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // Toggle detailed day view
            $('.day-header').on('click', function() {
                const target = $(this).data('target');
                $('#' + target).toggleClass('hidden');
                $(this).find('span').text(
                    $('#' + target).hasClass('hidden') ? '▼' : '▲'
                );
            });
        });
    </script>
    <script src="sort.js"></script>
</body>
</html>