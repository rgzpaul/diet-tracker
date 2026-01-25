<?php
// Initialize files if they don't exist
$mealsFile = 'data/meals.json';
$dailyMealsFile = 'data/daily_meals.json';

// Create directory if it doesn't exist
if (!is_dir('data')) {
    mkdir('data', 0777, true);
}

// Initialize meals database if it doesn't exist
if (!file_exists($mealsFile)) {
    $initialMeals = [
        [
            'name' => 'Oatmeal',
            'protein' => 10,
            'carbs' => 50,
            'fat' => 5,
            'color' => 'green'
        ],
        [
            'name' => 'Chicken Salad',
            'protein' => 30,
            'carbs' => 15,
            'fat' => 18,
            'color' => 'blue'
        ],
        [
            'name' => 'Protein Shake',
            'protein' => 25,
            'carbs' => 10,
            'fat' => 3,
            'color' => 'blue'
        ],
        [
            'name' => 'Salmon with Rice',
            'protein' => 35,
            'carbs' => 40,
            'fat' => 15,
            'color' => 'orange'
        ],
        [
            'name' => 'Greek Yogurt',
            'protein' => 15,
            'carbs' => 8,
            'fat' => 5,
            'color' => 'blue'
        ],
        [
            'name' => 'Banana',
            'protein' => 1,
            'carbs' => 27,
            'fat' => 0,
            'color' => 'brown'
        ]
    ];
    file_put_contents($mealsFile, json_encode($initialMeals, JSON_PRETTY_PRINT));
}

// Initialize daily meals database if it doesn't exist
if (!file_exists($dailyMealsFile)) {
    $initialDailyMeals = [];
    file_put_contents($dailyMealsFile, json_encode($initialDailyMeals, JSON_PRETTY_PRINT));
}

// Load the databases
$meals = json_decode(file_get_contents($mealsFile), true);
$dailyMeals = json_decode(file_get_contents($dailyMealsFile), true);

// Function to calculate kcal based on macros
function calculateKcal($protein, $carbs, $fat)
{
    return ($protein * 4) + ($carbs * 4) + ($fat * 9);
}

// Handle adding a meal
if (isset($_POST['add_meal'])) {
    $mealName = $_POST['meal_name'];
    $today = date('Y-m-d');
    $dayKey = date('D d/m', strtotime($today)); // Format: "LUN 22/03"

    // Find the meal in our database
    $mealToAdd = null;
    foreach ($meals as $meal) {
        if ($meal['name'] == $mealName) {
            $mealToAdd = $meal;
            break;
        }
    }

    // Add the meal to today's meals
    if ($mealToAdd) {
        if (!isset($dailyMeals[$today])) {
            $dailyMeals[$today] = [
                'date' => $dayKey,
                'meals' => []
            ];
        }

        $dailyMeals[$today]['meals'][] = [
            'name' => $mealToAdd['name'],
            'protein' => $mealToAdd['protein'],
            'carbs' => $mealToAdd['carbs'],
            'fat' => $mealToAdd['fat'],
            'color' => isset($mealToAdd['color']) ? $mealToAdd['color'] : 'blue'
        ];

        // Save the updated daily meals
        file_put_contents($dailyMealsFile, json_encode($dailyMeals, JSON_PRETTY_PRINT));

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle deleting a meal from today's meals
if (isset($_POST['delete_today_meal'])) {
    $mealIndex = (int)$_POST['meal_index'];
    $today = date('Y-m-d');

    // Check if today has meals and the index is valid
    if (isset($dailyMeals[$today]) && isset($dailyMeals[$today]['meals'][$mealIndex])) {
        // Remove the meal
        array_splice($dailyMeals[$today]['meals'], $mealIndex, 1);

        // Save the updated daily meals
        file_put_contents($dailyMealsFile, json_encode($dailyMeals, JSON_PRETTY_PRINT));
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Calculate totals for today
$today = date('Y-m-d');
$todaysMeals = isset($dailyMeals[$today]) ? $dailyMeals[$today]['meals'] : [];
$totalKcal = 0;
$totalProtein = 0;
$totalCarbs = 0;
$totalFat = 0;

foreach ($todaysMeals as $meal) {
    $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']);
    $totalKcal += $mealKcal;
    $totalProtein += $meal['protein'];
    $totalCarbs += $meal['carbs'];
    $totalFat += $meal['fat'];
}

// Format today's date for display
$displayDate = date('D d/m');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Tracker</title>
    <link rel="manifest" href="./manifest.json">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .meal-button {
            transition: all 0.15s ease;
        }
        .meal-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .meal-button:active {
            transform: translateY(0);
        }
    </style>
</head>

<body class="bg-stone-100 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex items-center justify-center gap-3 mb-8">
            <i data-lucide="utensils" class="w-7 h-7 text-stone-700"></i>
            <h1 class="text-2xl font-semibold text-stone-800">Daily Tracker</h1>
        </div>

        <!-- Today's Meals Table -->
        <div class="bg-white rounded-xl border border-stone-200 p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="calendar" class="w-5 h-5 text-stone-500"></i>
                <h2 class="text-lg font-medium text-stone-700"><?php echo strtoupper($displayDate); ?></h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-stone-200">
                            <th class="pb-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wide">Meal</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide">K</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide">P</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide">C</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide">F</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todaysMeals)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-stone-400">
                                    <i data-lucide="coffee" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p>No meals added today</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($todaysMeals as $index => $meal): ?>
                                <?php $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']); ?>
                                <tr class="border-b border-stone-100 hover:bg-stone-50 meal-row cursor-pointer transition-colors" data-index="<?php echo $index; ?>">
                                    <td class="py-3 text-stone-700"><?php echo htmlspecialchars($meal['name']); ?></td>
                                    <td class="py-3 text-center text-stone-600 font-medium"><?php echo $mealKcal; ?></td>
                                    <td class="py-3 text-center text-stone-500"><?php echo $meal['protein']; ?></td>
                                    <td class="py-3 text-center text-stone-500"><?php echo $meal['carbs']; ?></td>
                                    <td class="py-3 text-center text-stone-500"><?php echo $meal['fat']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Totals Row -->
                        <tr class="bg-stone-50">
                            <td class="py-3 font-semibold text-stone-800">Total</td>
                            <td class="py-3 text-center font-semibold text-stone-800"><?php echo $totalKcal; ?></td>
                            <td class="py-3 text-center font-medium text-stone-600"><?php echo $totalProtein; ?></td>
                            <td class="py-3 text-center font-medium text-stone-600"><?php echo $totalCarbs; ?></td>
                            <td class="py-3 text-center font-medium text-stone-600"><?php echo $totalFat; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Meal Buttons -->
        <div class="bg-white rounded-xl border border-stone-200 p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="plus-circle" class="w-5 h-5 text-stone-500"></i>
                <h2 class="text-lg font-medium text-stone-700">Add Meal</h2>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <?php foreach ($meals as $meal): ?>
                    <?php $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']); ?>
                    <form method="post" class="meal-form">
                        <input type="hidden" name="meal_name" value="<?php echo htmlspecialchars($meal['name']); ?>">
                        <button type="submit" name="add_meal"
                            class="meal-button h-full w-full
                            <?php
                            $buttonColor = isset($meal['color']) ? $meal['color'] : 'grey';
                            switch ($buttonColor) {
                                case 'brown':
                                    echo 'bg-stone-600 hover:bg-stone-700';
                                    break;
                                case 'green':
                                    echo 'bg-stone-700 hover:bg-stone-800';
                                    break;
                                case 'orange':
                                    echo 'bg-stone-500 hover:bg-stone-600';
                                    break;
                                case 'blue':
                                default:
                                    echo 'bg-stone-800 hover:bg-stone-900';
                                    break;
                            }
                            ?> text-white py-3 px-3 rounded-lg text-left">
                            <div class="font-medium text-sm"><?php echo htmlspecialchars($meal['name']); ?></div>
                            <div class="text-xs mt-1 opacity-80">
                                <?php echo $mealKcal; ?> kcal
                            </div>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex justify-center gap-3">
            <a href="report.php" class="inline-flex items-center gap-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 font-medium py-2.5 px-4 rounded-lg transition-colors">
                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                Report
            </a>
            <a href="create.php" class="inline-flex items-center gap-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 font-medium py-2.5 px-4 rounded-lg transition-colors">
                <i data-lucide="settings" class="w-4 h-4"></i>
                Manage
            </a>
        </div>
    </div>

    <!-- Delete Meal Form (Hidden) -->
    <form id="delete-meal-form" method="post" class="hidden">
        <input type="hidden" name="meal_index" id="meal-index-to-delete">
        <input type="hidden" name="delete_today_meal" value="1">
    </form>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        $(document).ready(function() {
            $('.meal-form').on('submit', function() {
                $(this).find('button').addClass('opacity-50').prop('disabled', true);
            });

            // Click event for meal rows
            $('.meal-row').on('click', function() {
                if (confirm('Delete this meal from today\'s log?')) {
                    const mealIndex = $(this).data('index');
                    $('#meal-index-to-delete').val(mealIndex);
                    $('#delete-meal-form').submit();
                }
            });
        });
    </script>
    <script src="sort.js"></script>
</body>

</html>
