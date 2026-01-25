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
            'color' => 'blue'
        ],
        [
            'name' => 'Chicken Salad',
            'protein' => 30,
            'carbs' => 15,
            'fat' => 18,
            'color' => 'green'
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
    <!-- Manifest and Icons for PWA -->
    <link rel="manifest" href="./manifest.json">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .meal-button:hover {
            transform: scale(1.05);
            transition: transform 0.2s;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-blue-700">Daily Meal Tracker</h1>

        <!-- Today's Meals Table -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4 text-center"><?php echo strtoupper($displayDate); ?></h2>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-blue-100">
                            <th class="border p-2 text-left">Meal</th>
                            <th class="border p-2 text-center">K</th>
                            <th class="border p-2 text-center">P</th>
                            <th class="border p-2 text-center">C</th>
                            <th class="border p-2 text-center">F</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todaysMeals)): ?>
                            <tr>
                                <td colspan="5" class="border p-4 text-center text-gray-500">No meals added today</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($todaysMeals as $index => $meal): ?>
                                <?php $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']); ?>
                                <tr class="hover:bg-gray-50 meal-row cursor-pointer" data-index="<?php echo $index; ?>">
                                    <td class="border p-2"><?php echo htmlspecialchars($meal['name']); ?></td>
                                    <td class="border p-2 text-center"><?php echo $mealKcal; ?></td>
                                    <td class="border p-2 text-center"><?php echo $meal['protein']; ?></td>
                                    <td class="border p-2 text-center"><?php echo $meal['carbs']; ?></td>
                                    <td class="border p-2 text-center"><?php echo $meal['fat']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Totals Row -->
                        <tr class="bg-blue-50 font-bold">
                            <td class="border p-2">TOTAL</td>
                            <td class="border p-2 text-center"><?php echo $totalKcal; ?></td>
                            <td class="border p-2 text-center"><?php echo $totalProtein; ?></td>
                            <td class="border p-2 text-center"><?php echo $totalCarbs; ?></td>
                            <td class="border p-2 text-center"><?php echo $totalFat; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Meal Buttons -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Add a Meal</h2>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($meals as $meal): ?>
                    <?php $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']); ?>
                    <form method="post" class="meal-form">
                        <input type="hidden" name="meal_name" value="<?php echo htmlspecialchars($meal['name']); ?>">
                        <button type="submit" name="add_meal"
                            class="meal-button h-full w-full 
                            <?php
                            $buttonColor = isset($meal['color']) ? $meal['color'] : 'blue';
                            switch ($buttonColor) {
                                case 'brown':
                                    echo 'bg-amber-800 hover:bg-amber-900';
                                    break;
                                case 'green':
                                    echo 'bg-green-800 hover:bg-green-900';
                                    break;
                                case 'orange':
                                    echo 'bg-orange-600 hover:bg-orange-700';
                                    break;
                                case 'blue':
                                default:
                                    echo 'bg-blue-600 hover:bg-blue-700';
                                    break;
                            }
                            ?> text-white font-medium py-3 px-4 rounded-lg text-center">
                            <div class="font-bold"><?php echo htmlspecialchars($meal['name']); ?></div>
                            <div class="text-sm mt-1">
                                <?php echo $mealKcal; ?> kcal |
                                P: <?php echo $meal['protein']; ?>g |
                                C: <?php echo $meal['carbs']; ?>g |
                                F: <?php echo $meal['fat']; ?>g
                            </div>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="text-center mb-8">
            <a href="report.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 mx-2 rounded-lg text-center">
                Weekly Report
            </a>
            <a href="create.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 mx-2 rounded-lg text-center">
                Manage Meals
            </a>
        </div>
    </div>

    <!-- Delete Meal Form (Hidden) -->
    <form id="delete-meal-form" method="post" class="hidden">
        <input type="hidden" name="meal_index" id="meal-index-to-delete">
        <input type="hidden" name="delete_today_meal" value="1">
    </form>

    <script>
        $(document).ready(function() {
            $('.meal-form').on('submit', function() {
                // Add a loading state or animation if desired
                $(this).find('button').addClass('bg-blue-800').text('Adding...');
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