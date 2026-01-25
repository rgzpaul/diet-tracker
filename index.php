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

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get selected date from URL parameter or default to today
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || !strtotime($selectedDate)) {
    $selectedDate = date('Y-m-d');
}
// Don't allow future dates
if (strtotime($selectedDate) > strtotime(date('Y-m-d'))) {
    $selectedDate = date('Y-m-d');
}
$isToday = ($selectedDate === date('Y-m-d'));

// Handle adding a meal
if (isset($_POST['add_meal'])) {
    $mealName = $_POST['meal_name'];
    $targetDate = isset($_POST['target_date']) ? $_POST['target_date'] : date('Y-m-d');
    $dayKey = date('D d/m', strtotime($targetDate));

    // Find the meal in our database
    $mealToAdd = null;
    foreach ($meals as $meal) {
        if ($meal['name'] == $mealName) {
            $mealToAdd = $meal;
            break;
        }
    }

    // Add the meal to target date's meals
    if ($mealToAdd) {
        if (!isset($dailyMeals[$targetDate])) {
            $dailyMeals[$targetDate] = [
                'date' => $dayKey,
                'meals' => []
            ];
        }

        $newMeal = [
            'name' => $mealToAdd['name'],
            'protein' => $mealToAdd['protein'],
            'carbs' => $mealToAdd['carbs'],
            'fat' => $mealToAdd['fat'],
            'color' => isset($mealToAdd['color']) ? $mealToAdd['color'] : 'blue'
        ];
        $dailyMeals[$targetDate]['meals'][] = $newMeal;

        // Save the updated daily meals
        file_put_contents($dailyMealsFile, json_encode($dailyMeals, JSON_PRETTY_PRINT));

        if ($isAjax) {
            // Calculate new totals
            $dateMeals = $dailyMeals[$targetDate]['meals'];
            $totalKcal = 0;
            $totalProtein = 0;
            $totalCarbs = 0;
            $totalFat = 0;
            foreach ($dateMeals as $m) {
                $totalKcal += calculateKcal($m['protein'], $m['carbs'], $m['fat']);
                $totalProtein += $m['protein'];
                $totalCarbs += $m['carbs'];
                $totalFat += $m['fat'];
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'meal' => $newMeal,
                'mealKcal' => calculateKcal($newMeal['protein'], $newMeal['carbs'], $newMeal['fat']),
                'mealIndex' => count($dateMeals) - 1,
                'totals' => [
                    'kcal' => $totalKcal,
                    'protein' => $totalProtein,
                    'carbs' => $totalCarbs,
                    'fat' => $totalFat
                ]
            ]);
            exit;
        }

        // Redirect to prevent form resubmission (non-AJAX fallback)
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Meal not found']);
        exit;
    }
}

// Handle deleting a meal from selected date's meals
if (isset($_POST['delete_today_meal'])) {
    $mealIndex = (int)$_POST['meal_index'];
    $targetDate = isset($_POST['target_date']) ? $_POST['target_date'] : date('Y-m-d');

    // Check if date has meals and the index is valid
    if (isset($dailyMeals[$targetDate]) && isset($dailyMeals[$targetDate]['meals'][$mealIndex])) {
        // Remove the meal
        array_splice($dailyMeals[$targetDate]['meals'], $mealIndex, 1);

        // Save the updated daily meals
        file_put_contents($dailyMealsFile, json_encode($dailyMeals, JSON_PRETTY_PRINT));

        if ($isAjax) {
            // Calculate new totals
            $dateMeals = $dailyMeals[$targetDate]['meals'];
            $totalKcal = 0;
            $totalProtein = 0;
            $totalCarbs = 0;
            $totalFat = 0;
            foreach ($dateMeals as $m) {
                $totalKcal += calculateKcal($m['protein'], $m['carbs'], $m['fat']);
                $totalProtein += $m['protein'];
                $totalCarbs += $m['carbs'];
                $totalFat += $m['fat'];
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'totals' => [
                    'kcal' => $totalKcal,
                    'protein' => $totalProtein,
                    'carbs' => $totalCarbs,
                    'fat' => $totalFat
                ],
                'mealsCount' => count($dateMeals)
            ]);
            exit;
        }
    } else if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Meal not found']);
        exit;
    }

    // Redirect to prevent form resubmission (non-AJAX fallback)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Calculate totals for selected date
$selectedDateMeals = isset($dailyMeals[$selectedDate]) ? $dailyMeals[$selectedDate]['meals'] : [];
$totalKcal = 0;
$totalProtein = 0;
$totalCarbs = 0;
$totalFat = 0;

foreach ($selectedDateMeals as $meal) {
    $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']);
    $totalKcal += $mealKcal;
    $totalProtein += $meal['protein'];
    $totalCarbs += $meal['carbs'];
    $totalFat += $meal['fat'];
}

// Format selected date for display
$displayDate = date('D d/m', strtotime($selectedDate));
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

        <!-- Selected Date's Meals Table -->
        <div class="bg-white rounded-xl border border-stone-200 p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <div id="date-picker-trigger" class="flex items-center gap-2 cursor-pointer hover:bg-stone-100 rounded-lg px-2 py-1 -mx-2 -my-1 transition-colors">
                    <i data-lucide="calendar" class="w-5 h-5 text-stone-500"></i>
                    <h2 class="text-lg font-medium text-stone-700"><?php echo strtoupper($displayDate); ?></h2>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-stone-400"></i>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-stone-200">
                            <th class="pb-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wide">Meal</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">K</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">P</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">C</th>
                            <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">F</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($selectedDateMeals)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-stone-400">
                                    <i data-lucide="coffee" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    <p>No meals added <?php echo $isToday ? 'today' : 'on this day'; ?></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($selectedDateMeals as $index => $meal): ?>
                                <?php $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']); ?>
                                <tr class="border-b border-stone-100 hover:bg-stone-50 meal-row cursor-pointer transition-colors" data-index="<?php echo $index; ?>" data-name="<?php echo htmlspecialchars($meal['name']); ?>">
                                    <td class="py-3 px-1 text-stone-700"><?php echo htmlspecialchars($meal['name']); ?></td>
                                    <td class="py-3 px-1 text-center text-stone-600 font-medium border-l border-stone-100"><?php echo $mealKcal; ?></td>
                                    <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100"><?php echo $meal['protein']; ?></td>
                                    <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100"><?php echo $meal['carbs']; ?></td>
                                    <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100"><?php echo $meal['fat']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Totals Row -->
                        <tr class="bg-stone-100">
                            <td class="py-3 px-1 font-semibold text-stone-800">Total</td>
                            <td class="py-3 px-1 text-center font-semibold text-stone-800 border-l border-stone-100"><?php echo $totalKcal; ?></td>
                            <td class="py-3 px-1 text-center font-medium text-stone-600 border-l border-stone-100"><?php echo $totalProtein; ?></td>
                            <td class="py-3 px-1 text-center font-medium text-stone-600 border-l border-stone-100"><?php echo $totalCarbs; ?></td>
                            <td class="py-3 px-1 text-center font-medium text-stone-600 border-l border-stone-100"><?php echo $totalFat; ?></td>
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
                    <div class="relative">
                        <form method="post" class="meal-form h-full">
                            <input type="hidden" name="meal_name" value="<?php echo htmlspecialchars($meal['name']); ?>">
                            <input type="hidden" name="target_date" value="<?php echo $selectedDate; ?>">
                            <button type="submit" name="add_meal"
                                class="meal-button h-full w-full
                                <?php
                                $buttonColor = isset($meal['color']) ? $meal['color'] : 'grey';
                                switch ($buttonColor) {
                                    case 'brown':
                                        echo 'bg-amber-700 hover:bg-amber-800';
                                        break;
                                    case 'orange':
                                        echo 'bg-orange-500 hover:bg-orange-600';
                                        break;
                                    case 'blue':
                                    default:
                                        echo 'bg-blue-600 hover:bg-blue-700';
                                        break;
                                }
                                ?> text-white py-3 px-3 rounded-lg text-left pr-8">
                                <div class="font-medium text-sm"><?php echo htmlspecialchars($meal['name']); ?></div>
                                <div class="text-xs mt-1 opacity-80">
                                    <?php echo $mealKcal; ?> kcal
                                </div>
                            </button>
                        </form>
                        <button type="button"
                            class="info-btn absolute top-2 right-2 p-1 text-white/70 hover:text-white hover:bg-white/20 rounded transition-colors"
                            data-name="<?php echo htmlspecialchars($meal['name']); ?>"
                            data-description="<?php echo htmlspecialchars(isset($meal['description']) ? $meal['description'] : ''); ?>"
                            title="View description">
                            <i data-lucide="info" class="w-4 h-4"></i>
                        </button>
                    </div>
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

    <!-- Description Modal -->
    <div id="description-modal" class="fixed inset-0 bg-stone-900/50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl border border-stone-200 p-6 w-full max-w-md mx-4">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="info" class="w-5 h-5 text-stone-500"></i>
                <h3 id="description-modal-title" class="text-lg font-medium text-stone-700">Meal Description</h3>
            </div>
            <div id="description-modal-content" class="text-stone-600 mb-4 whitespace-pre-wrap">
                No description available.
            </div>
            <div class="flex justify-end">
                <button type="button" id="close-description-modal" class="px-4 py-2 text-stone-600 bg-stone-100 rounded-lg hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-stone-400 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Date Picker Modal -->
    <div id="date-picker-modal" class="fixed inset-0 bg-stone-900/50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl border border-stone-200 p-6 w-full max-w-sm mx-4">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="calendar" class="w-5 h-5 text-stone-500"></i>
                <h3 class="text-lg font-medium text-stone-700">Select Date</h3>
            </div>
            <div class="mb-4">
                <input type="date" id="date-picker-input"
                    value="<?php echo $selectedDate; ?>"
                    max="<?php echo date('Y-m-d'); ?>"
                    class="w-full px-4 py-3 border border-stone-300 rounded-lg text-stone-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg">
            </div>
            <div class="flex gap-3">
                <button type="button" id="date-picker-today" class="flex-1 px-4 py-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 font-medium transition-colors">
                    Today
                </button>
                <button type="button" id="close-date-picker" class="flex-1 px-4 py-2 text-stone-600 bg-stone-100 rounded-lg hover:bg-stone-200 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Current selected date
        const selectedDate = '<?php echo $selectedDate; ?>';

        $(document).ready(function() {
            // Date picker functionality
            $('#date-picker-trigger').on('click', function() {
                $('#date-picker-modal').removeClass('hidden');
            });

            $('#close-date-picker').on('click', function() {
                $('#date-picker-modal').addClass('hidden');
            });

            $('#date-picker-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).addClass('hidden');
                }
            });

            $('#date-picker-input').on('change', function() {
                const newDate = $(this).val();
                if (newDate) {
                    window.location.href = 'index.php?date=' + newDate;
                }
            });

            $('#date-picker-today').on('click', function() {
                window.location.href = 'index.php';
            });

            // AJAX handler for adding meals
            $('.meal-form').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $button = $form.find('button');
                const mealName = $form.find('input[name="meal_name"]').val();
                const targetDate = $form.find('input[name="target_date"]').val();

                $button.addClass('opacity-50').prop('disabled', true);

                $.ajax({
                    url: 'index.php?date=' + targetDate,
                    method: 'POST',
                    data: {
                        add_meal: 1,
                        meal_name: mealName,
                        target_date: targetDate
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove "No meals" message if present
                            const $noMealsRow = $('tbody tr td[colspan="5"]').closest('tr');
                            if ($noMealsRow.length) {
                                $noMealsRow.remove();
                            }

                            // Create new meal row
                            const newRow = `
                                <tr class="border-b border-stone-100 hover:bg-stone-50 meal-row cursor-pointer transition-colors" data-index="${response.mealIndex}" data-name="${escapeHtml(response.meal.name)}" style="opacity: 0;">
                                    <td class="py-3 px-1 text-stone-700">${escapeHtml(response.meal.name)}</td>
                                    <td class="py-3 px-1 text-center text-stone-600 font-medium border-l border-stone-100">${response.mealKcal}</td>
                                    <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100">${response.meal.protein}</td>
                                    <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100">${response.meal.carbs}</td>
                                    <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100">${response.meal.fat}</td>
                                </tr>
                            `;

                            // Insert before totals row
                            const $totalsRow = $('tbody tr.bg-stone-100');
                            $(newRow).insertBefore($totalsRow).animate({opacity: 1}, 200);

                            // Update totals
                            updateTotals(response.totals);

                            // Rebind click events
                            bindMealRowEvents();
                        }
                        $button.removeClass('opacity-50').prop('disabled', false);
                    },
                    error: function() {
                        $button.removeClass('opacity-50').prop('disabled', false);
                        alert('Error adding meal. Please try again.');
                    }
                });
            });

            // Function to bind meal row click events
            function bindMealRowEvents() {
                $('.meal-row').off('click').on('click', function() {
                    const $row = $(this);
                    const mealIndex = $row.data('index');
                    const mealName = $row.data('name');

                    if (confirm('Delete ' + mealName + ' from the log?')) {

                        $.ajax({
                            url: 'index.php?date=' + selectedDate,
                            method: 'POST',
                            data: {
                                delete_today_meal: 1,
                                meal_index: mealIndex,
                                target_date: selectedDate
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    // Animate row removal
                                    $row.animate({opacity: 0}, 200, function() {
                                        $(this).remove();

                                        // Update totals
                                        updateTotals(response.totals);

                                        // Re-index remaining rows
                                        reindexMealRows();

                                        // Show "No meals" if empty
                                        if (response.mealsCount === 0) {
                                            const isToday = selectedDate === '<?php echo date('Y-m-d'); ?>';
                                            const emptyRow = `
                                                <tr>
                                                    <td colspan="5" class="py-8 text-center text-stone-400">
                                                        <i data-lucide="coffee" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                                        <p>No meals added ${isToday ? 'today' : 'on this day'}</p>
                                                    </td>
                                                </tr>
                                            `;
                                            $(emptyRow).insertBefore($('tbody tr.bg-stone-100'));
                                            lucide.createIcons();
                                        }
                                    });
                                }
                            },
                            error: function() {
                                alert('Error deleting meal. Please try again.');
                            }
                        });
                    }
                });
            }

            // Initial binding
            bindMealRowEvents();

            // Function to update totals
            function updateTotals(totals) {
                const $totalsRow = $('tbody tr.bg-stone-100');
                $totalsRow.find('td:eq(1)').text(totals.kcal);
                $totalsRow.find('td:eq(2)').text(totals.protein);
                $totalsRow.find('td:eq(3)').text(totals.carbs);
                $totalsRow.find('td:eq(4)').text(totals.fat);
            }

            // Function to re-index meal rows after deletion
            function reindexMealRows() {
                $('.meal-row').each(function(index) {
                    $(this).data('index', index);
                    $(this).attr('data-index', index);
                });
            }

            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Info button click (show description)
            $('.info-btn').on('click', function(e) {
                e.stopPropagation();
                const name = $(this).data('name');
                const description = $(this).data('description') || '';

                $('#description-modal-title').text(name);
                $('#description-modal-content').text(description || 'No description available.');
                $('#description-modal').removeClass('hidden');
                lucide.createIcons();
            });

            // Close description modal
            $('#close-description-modal').on('click', function() {
                $('#description-modal').addClass('hidden');
            });

            // Close modal when clicking outside
            $('#description-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).addClass('hidden');
                }
            });
        });
    </script>
    <script src="sort.js"></script>
</body>

</html>
