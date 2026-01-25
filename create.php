<?php
// Initialize file path
$mealsFile = 'data/meals.json';

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
        ]
    ];
    file_put_contents($mealsFile, json_encode($initialMeals, JSON_PRETTY_PRINT));
}

// Load the database
$meals = json_decode(file_get_contents($mealsFile), true);

// Function to calculate kcal based on macros
function calculateKcal($protein, $carbs, $fat)
{
    return ($protein * 4) + ($carbs * 4) + ($fat * 9);
}

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Handle form submissions
$message = '';
$messageType = '';

// Add new meal
if (isset($_POST['add_meal'])) {
    $name = trim($_POST['name']);
    $protein = floatval($_POST['protein']);
    $carbs = floatval($_POST['carbs']);
    $fat = floatval($_POST['fat']);
    $color = $_POST['color'];

    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = "Meal name is required";
    }

    if ($protein < 0 || $carbs < 0 || $fat < 0) {
        $errors[] = "Macronutrient values cannot be negative";
    }

    // Check for duplicate name
    $duplicate = false;
    foreach ($meals as $meal) {
        if (strtolower($meal['name']) === strtolower($name)) {
            $duplicate = true;
            break;
        }
    }

    if ($duplicate) {
        $errors[] = "A meal with this name already exists";
    }

    if (empty($errors)) {
        // Add the new meal
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $newMeal = [
            'name' => $name,
            'protein' => $protein,
            'carbs' => $carbs,
            'fat' => $fat,
            'color' => $color,
            'description' => $description
        ];
        $meals[] = $newMeal;

        // Save the updated meals
        file_put_contents($mealsFile, json_encode($meals, JSON_PRETTY_PRINT));

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Meal added successfully!',
                'meal' => $newMeal,
                'kcal' => calculateKcal($protein, $carbs, $fat)
            ]);
            exit;
        }

        $message = "Meal added successfully!";
        $messageType = "success";

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . $messageType);
        exit;
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}

// Update meal
if (isset($_POST['update_meal'])) {
    $oldName = trim($_POST['old_name']);
    $newName = trim($_POST['name']);
    $protein = floatval($_POST['protein']);
    $carbs = floatval($_POST['carbs']);
    $fat = floatval($_POST['fat']);
    $color = $_POST['color'];

    // Validation
    $errors = [];
    if (empty($newName)) {
        $errors[] = "Meal name is required";
    }

    if ($protein < 0 || $carbs < 0 || $fat < 0) {
        $errors[] = "Macronutrient values cannot be negative";
    }

    // Check for duplicate name only if name has changed
    if (strtolower($oldName) !== strtolower($newName)) {
        $duplicate = false;
        foreach ($meals as $meal) {
            if (strtolower($meal['name']) === strtolower($newName)) {
                $duplicate = true;
                break;
            }
        }

        if ($duplicate) {
            $errors[] = "A meal with this name already exists";
        }
    }

    if (empty($errors)) {
        // Update the meal
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $updatedMeal = null;
        foreach ($meals as $key => $meal) {
            if ($meal['name'] === $oldName) {
                $meals[$key] = [
                    'name' => $newName,
                    'protein' => $protein,
                    'carbs' => $carbs,
                    'fat' => $fat,
                    'color' => $color,
                    'description' => $description
                ];
                $updatedMeal = $meals[$key];
                break;
            }
        }

        // Save the updated meals
        file_put_contents($mealsFile, json_encode($meals, JSON_PRETTY_PRINT));

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Meal updated successfully!',
                'meal' => $updatedMeal,
                'oldName' => $oldName,
                'kcal' => calculateKcal($protein, $carbs, $fat)
            ]);
            exit;
        }

        $message = "Meal updated successfully!";
        $messageType = "success";

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . $messageType);
        exit;
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}

// Delete meal
if (isset($_POST['delete_meal'])) {
    $name = $_POST['meal_name'];

    // Find and remove the meal
    foreach ($meals as $key => $meal) {
        if ($meal['name'] === $name) {
            unset($meals[$key]);
            break;
        }
    }

    // Reindex array
    $meals = array_values($meals);

    // Save the updated meals
    file_put_contents($mealsFile, json_encode($meals, JSON_PRETTY_PRINT));

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Meal deleted successfully!',
            'deletedName' => $name,
            'mealsCount' => count($meals)
        ]);
        exit;
    }

    $message = "Meal deleted successfully!";
    $messageType = "success";

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

// Handle URL message parameter
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Sort meals alphabetically by name
usort($meals, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Meals - Meal Tracker</title>
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
        <div class="flex items-center justify-center gap-3 mb-8">
            <i data-lucide="chef-hat" class="w-7 h-7 text-stone-700"></i>
            <h1 class="text-2xl font-semibold text-stone-800">Manage Meals</h1>
        </div>

        <!-- Message Display -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg flex items-center gap-3 <?php echo $messageType === 'success' ? 'bg-stone-200 text-stone-700' : 'bg-red-50 text-red-700'; ?>">
                <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5 flex-shrink-0"></i>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <!-- Add New Meal Form -->
        <div class="bg-white rounded-xl border border-stone-200 p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="plus" class="w-5 h-5 text-stone-500"></i>
                <h2 class="text-lg font-medium text-stone-700">Add New Meal</h2>
            </div>

            <form method="post" id="add-meal-form" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-stone-600 mb-1.5">Meal Name</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label for="protein" class="block text-sm font-medium text-stone-600 mb-1.5">Protein</label>
                            <input type="number" id="protein" name="protein" min="0" step="0.1" required value="0"
                                class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label for="carbs" class="block text-sm font-medium text-stone-600 mb-1.5">Carbs</label>
                            <input type="number" id="carbs" name="carbs" min="0" step="0.1" required value="0"
                                class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label for="fat" class="block text-sm font-medium text-stone-600 mb-1.5">Fat</label>
                            <input type="number" id="fat" name="fat" min="0" step="0.1" required value="0"
                                class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>

                <div>
                    <label for="color" class="block text-sm font-medium text-stone-600 mb-1.5">Category</label>
                    <select id="color" name="color"
                        class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                        <option value="blue">Protein-rich</option>
                        <option value="brown">Snacks</option>
                        <option value="orange">Mixed</option>
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-stone-600 mb-1.5">Description (optional)</label>
                    <textarea id="description" name="description" rows="2"
                        class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all resize-none"
                        placeholder="Add notes about ingredients, preparation, etc."></textarea>
                </div>

                <div class="flex justify-between items-center pt-2">
                    <div class="text-sm text-stone-500 flex items-center gap-2">
                        <i data-lucide="flame" class="w-4 h-4"></i>
                        <span id="estimated-kcal">0</span> kcal
                    </div>
                    <button type="submit" name="add_meal" class="inline-flex items-center gap-2 px-4 py-2 bg-stone-800 text-white rounded-lg hover:bg-stone-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-stone-500 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add Meal
                    </button>
                </div>
            </form>
        </div>

        <!-- Existing Meals -->
        <div class="bg-white rounded-xl border border-stone-200 p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="list" class="w-5 h-5 text-stone-500"></i>
                <h2 class="text-lg font-medium text-stone-700">Existing Meals</h2>
            </div>

            <?php if (empty($meals)): ?>
                <div class="py-8 text-center text-stone-400">
                    <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
                    <p>No meals have been added yet</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full meals-table">
                        <thead>
                            <tr class="border-b border-stone-200">
                                <th class="pb-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wide">Meal</th>
                                <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">K</th>
                                <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">P</th>
                                <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">C</th>
                                <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100">F</th>
                                <th class="pb-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wide border-l border-stone-100"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meals as $meal): ?>
                                <?php $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']); ?>
                                <tr class="border-b border-stone-100 hover:bg-stone-50 transition-colors">
                                    <td class="py-3 px-1 text-stone-700">
                                        <div class="flex items-center justify-between gap-2">
                                            <span><?php echo htmlspecialchars($meal['name']); ?></span>
                                            <button type="button"
                                                class="info-btn p-1 text-stone-400 hover:text-stone-600 hover:bg-stone-100 rounded transition-colors flex-shrink-0"
                                                data-name="<?php echo htmlspecialchars($meal['name']); ?>"
                                                data-description="<?php echo htmlspecialchars(isset($meal['description']) ? $meal['description'] : ''); ?>"
                                                title="View description">
                                                <i data-lucide="info" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-3 px-1 text-center text-stone-600 font-medium border-l border-stone-100"><?php echo $mealKcal; ?></td>
                                    <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100"><?php echo $meal['protein']; ?></td>
                                    <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100"><?php echo $meal['carbs']; ?></td>
                                    <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100"><?php echo $meal['fat']; ?></td>
                                    <td class="py-3 px-1 text-center border-l border-stone-100">
                                        <div class="flex max-sm:flex-col itens-center justify-center gap-1">
                                            <button type="button"
                                                class="edit-meal-btn p-2 flex justify-center text-stone-500 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors"
                                                data-name="<?php echo htmlspecialchars($meal['name']); ?>"
                                                data-protein="<?php echo $meal['protein']; ?>"
                                                data-carbs="<?php echo $meal['carbs']; ?>"
                                                data-fat="<?php echo $meal['fat']; ?>"
                                                data-color="<?php echo isset($meal['color']) ? $meal['color'] : 'blue'; ?>"
                                                data-description="<?php echo htmlspecialchars(isset($meal['description']) ? $meal['description'] : ''); ?>">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                            </button>

                                            <form method="post" class="delete-form inline" onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($meal['name']); ?>?');">
                                                <input type="hidden" name="meal_name" value="<?php echo htmlspecialchars($meal['name']); ?>">
                                                <button type="submit" name="delete_meal" class="p-2 text-stone-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit Meal Modal -->
        <div id="edit-modal" class="fixed inset-0 bg-stone-900/50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-xl border border-stone-200 p-6 w-full max-w-md mx-4">
                <div class="flex items-center gap-2 mb-4">
                    <i data-lucide="edit-3" class="w-5 h-5 text-stone-500"></i>
                    <h3 class="text-lg font-medium text-stone-700">Edit Meal</h3>
                </div>

                <form method="post" id="edit-meal-form" class="space-y-4">
                    <input type="hidden" id="edit-old-name" name="old_name">

                    <div>
                        <label for="edit-name" class="block text-sm font-medium text-stone-600 mb-1.5">Meal Name</label>
                        <input type="text" id="edit-name" name="name" required
                            class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label for="edit-protein" class="block text-sm font-medium text-stone-600 mb-1.5">P</label>
                            <input type="number" id="edit-protein" name="protein" min="0" step="0.1" required
                                class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label for="edit-carbs" class="block text-sm font-medium text-stone-600 mb-1.5">C</label>
                            <input type="number" id="edit-carbs" name="carbs" min="0" step="0.1" required
                                class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label for="edit-fat" class="block text-sm font-medium text-stone-600 mb-1.5">F</label>
                            <input type="number" id="edit-fat" name="fat" min="0" step="0.1" required
                                class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="edit-color" class="block text-sm font-medium text-stone-600 mb-1.5">Category</label>
                        <select id="edit-color" name="color"
                            class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all">
                            <option value="blue">Protein-rich</option>
                            <option value="green">Vegetables</option>
                            <option value="brown">Snacks</option>
                            <option value="orange">Mixed</option>
                        </select>
                    </div>

                    <div>
                        <label for="edit-description" class="block text-sm font-medium text-stone-600 mb-1.5">Description</label>
                        <textarea id="edit-description" name="description" rows="2"
                            class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:border-transparent transition-all resize-none"
                            placeholder="Add notes about ingredients, preparation, etc."></textarea>
                    </div>

                    <div class="text-sm text-stone-500 flex items-center gap-2">
                        <i data-lucide="flame" class="w-4 h-4"></i>
                        <span id="edit-estimated-kcal">0</span> kcal
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="close-modal" class="px-4 py-2 text-stone-600 bg-stone-100 rounded-lg hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-stone-400 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" name="update_meal" class="inline-flex items-center gap-2 px-4 py-2 bg-stone-800 text-white rounded-lg hover:bg-stone-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-stone-500 transition-colors">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            Update
                        </button>
                    </div>
                </form>
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

        <!-- Navigation Links -->
        <div class="flex justify-center gap-3">
            <a href="index.php" class="inline-flex items-center gap-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 font-medium py-2.5 px-4 rounded-lg transition-colors">
                <i data-lucide="utensils" class="w-4 h-4"></i>
                Tracker
            </a>
            <a href="report.php" class="inline-flex items-center gap-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 font-medium py-2.5 px-4 rounded-lg transition-colors">
                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                Report
            </a>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        $(document).ready(function() {
            // Calculate estimated KCAL for new meal form
            function updateEstimatedKcal() {
                const protein = parseFloat($('#protein').val()) || 0;
                const carbs = parseFloat($('#carbs').val()) || 0;
                const fat = parseFloat($('#fat').val()) || 0;

                const kcal = (protein * 4) + (carbs * 4) + (fat * 9);
                $('#estimated-kcal').text(Math.round(kcal));
            }

            // Calculate estimated KCAL for edit form
            function updateEditEstimatedKcal() {
                const protein = parseFloat($('#edit-protein').val()) || 0;
                const carbs = parseFloat($('#edit-carbs').val()) || 0;
                const fat = parseFloat($('#edit-fat').val()) || 0;

                const kcal = (protein * 4) + (carbs * 4) + (fat * 9);
                $('#edit-estimated-kcal').text(Math.round(kcal));
            }

            // Update KCAL preview on input change
            $('#protein, #carbs, #fat').on('input', updateEstimatedKcal);
            '#edit-protein, #edit-carbs, #edit-fat'.split(', ').forEach(function(sel) {
                $(sel).on('input', updateEditEstimatedKcal);
            });
            $('#edit-protein, #edit-carbs, #edit-fat').on('input', updateEditEstimatedKcal);

            // Initialize KCAL preview
            updateEstimatedKcal();

            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Function to show toast message
            function showToast(message, type) {
                // Remove existing toast
                $('.toast-message').remove();

                const bgColor = type === 'success' ? 'bg-stone-200 text-stone-700' : 'bg-red-50 text-red-700';
                const icon = type === 'success' ? 'check-circle' : 'alert-circle';

                const toast = $(`
                    <div class="toast-message fixed top-4 right-4 p-4 rounded-lg flex items-center gap-3 ${bgColor} shadow-lg z-50" style="opacity: 0;">
                        <i data-lucide="${icon}" class="w-5 h-5 flex-shrink-0"></i>
                        <span>${escapeHtml(message)}</span>
                    </div>
                `);

                $('body').append(toast);
                lucide.createIcons();
                toast.animate({opacity: 1}, 200);

                setTimeout(function() {
                    toast.animate({opacity: 0}, 200, function() {
                        $(this).remove();
                    });
                }, 3000);
            }

            // AJAX handler for adding new meal
            $('#add-meal-form').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $button = $form.find('button[name="add_meal"]');

                $button.addClass('opacity-50').prop('disabled', true);

                $.ajax({
                    url: 'create.php',
                    method: 'POST',
                    data: $form.serialize() + '&add_meal=1',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Add new row to table
                            const $tbody = $('.meals-table tbody');
                            const $emptyMessage = $tbody.find('.empty-message');
                            if ($emptyMessage.length) {
                                $emptyMessage.closest('tr').remove();
                            }

                            const newRow = createMealRow(response.meal, response.kcal);

                            // Insert in alphabetical order
                            let inserted = false;
                            $tbody.find('tr').each(function() {
                                const rowName = $(this).find('td:first').text();
                                if (response.meal.name.toLowerCase() < rowName.toLowerCase()) {
                                    $(newRow).insertBefore(this);
                                    inserted = true;
                                    return false;
                                }
                            });
                            if (!inserted) {
                                $tbody.append(newRow);
                            }

                            // Reinitialize icons and events
                            lucide.createIcons();
                            bindMealEvents();

                            // Clear form
                            $form[0].reset();
                            updateEstimatedKcal();

                            showToast(response.message, 'success');
                        } else {
                            showToast(response.errors.join(', '), 'error');
                        }
                        $button.removeClass('opacity-50').prop('disabled', false);
                    },
                    error: function() {
                        showToast('Error adding meal. Please try again.', 'error');
                        $button.removeClass('opacity-50').prop('disabled', false);
                    }
                });
            });

            // AJAX handler for updating meal
            $('#edit-meal-form').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $button = $form.find('button[name="update_meal"]');

                $button.addClass('opacity-50').prop('disabled', true);

                $.ajax({
                    url: 'create.php',
                    method: 'POST',
                    data: $form.serialize() + '&update_meal=1',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Find and update the row
                            const $row = $(`.edit-meal-btn[data-name="${escapeHtml(response.oldName)}"]`).closest('tr');

                            // Update row content
                            $row.find('td:eq(0) span').first().text(response.meal.name);
                            $row.find('td:eq(1)').text(response.kcal);
                            $row.find('td:eq(2)').text(response.meal.protein);
                            $row.find('td:eq(3)').text(response.meal.carbs);
                            $row.find('td:eq(4)').text(response.meal.fat);

                            // Update button data attributes
                            const $editBtn = $row.find('.edit-meal-btn');
                            const description = response.meal.description || '';
                            $editBtn.data('name', response.meal.name);
                            $editBtn.data('protein', response.meal.protein);
                            $editBtn.data('carbs', response.meal.carbs);
                            $editBtn.data('fat', response.meal.fat);
                            $editBtn.data('color', response.meal.color);
                            $editBtn.data('description', description);
                            $editBtn.attr('data-name', response.meal.name);
                            $editBtn.attr('data-protein', response.meal.protein);
                            $editBtn.attr('data-carbs', response.meal.carbs);
                            $editBtn.attr('data-fat', response.meal.fat);
                            $editBtn.attr('data-color', response.meal.color);
                            $editBtn.attr('data-description', description);

                            // Update info button data attributes
                            const $infoBtn = $row.find('.info-btn');
                            $infoBtn.data('name', response.meal.name);
                            $infoBtn.data('description', description);
                            $infoBtn.attr('data-name', response.meal.name);
                            $infoBtn.attr('data-description', description);

                            // Update delete form hidden input
                            $row.find('.delete-form input[name="meal_name"]').val(response.meal.name);

                            // Close modal
                            $('#edit-modal').addClass('hidden');

                            showToast(response.message, 'success');
                        } else {
                            showToast(response.errors.join(', '), 'error');
                        }
                        $button.removeClass('opacity-50').prop('disabled', false);
                    },
                    error: function() {
                        showToast('Error updating meal. Please try again.', 'error');
                        $button.removeClass('opacity-50').prop('disabled', false);
                    }
                });
            });

            // Function to create a new meal row
            function createMealRow(meal, kcal) {
                const description = meal.description || '';
                return `
                    <tr class="border-b border-stone-100 hover:bg-stone-50 transition-colors">
                        <td class="py-3 px-1 text-stone-700">
                            <div class="flex items-center justify-between gap-2">
                                <span>${escapeHtml(meal.name)}</span>
                                <button type="button"
                                    class="info-btn p-1 text-stone-400 hover:text-stone-600 hover:bg-stone-100 rounded transition-colors flex-shrink-0"
                                    data-name="${escapeHtml(meal.name)}"
                                    data-description="${escapeHtml(description)}"
                                    title="View description">
                                    <i data-lucide="info" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                        <td class="py-3 px-1 text-center text-stone-600 font-medium border-l border-stone-100">${kcal}</td>
                        <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100">${meal.protein}</td>
                        <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100">${meal.carbs}</td>
                        <td class="py-3 px-1 text-center text-stone-500 border-l border-stone-100">${meal.fat}</td>
                        <td class="py-3 px-1 text-center border-l border-stone-100">
                            <div class="flex max-sm:flex-col itens-center justify-center gap-1">
                                <button type="button"
                                    class="edit-meal-btn p-2 flex justify-center text-stone-500 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors"
                                    data-name="${escapeHtml(meal.name)}"
                                    data-protein="${meal.protein}"
                                    data-carbs="${meal.carbs}"
                                    data-fat="${meal.fat}"
                                    data-color="${meal.color}"
                                    data-description="${escapeHtml(description)}">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <form method="post" class="delete-form inline">
                                    <input type="hidden" name="meal_name" value="${escapeHtml(meal.name)}">
                                    <button type="submit" name="delete_meal" class="p-2 text-stone-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                `;
            }

            // Function to bind meal events (edit and delete)
            function bindMealEvents() {
                // Edit meal button click
                $('.edit-meal-btn').off('click').on('click', function() {
                    const name = $(this).data('name');
                    const protein = $(this).data('protein');
                    const carbs = $(this).data('carbs');
                    const fat = $(this).data('fat');
                    const color = $(this).data('color');
                    const description = $(this).data('description') || '';

                    $('#edit-old-name').val(name);
                    $('#edit-name').val(name);
                    $('#edit-protein').val(protein);
                    $('#edit-carbs').val(carbs);
                    $('#edit-fat').val(fat);
                    $('#edit-color').val(color);
                    $('#edit-description').val(description);

                    updateEditEstimatedKcal();

                    $('#edit-modal').removeClass('hidden');
                    lucide.createIcons();
                });

                // Info button click (show description)
                $('.info-btn').off('click').on('click', function() {
                    const name = $(this).data('name');
                    const description = $(this).data('description') || '';

                    $('#description-modal-title').text(name);
                    $('#description-modal-content').text(description || 'No description available.');
                    $('#description-modal').removeClass('hidden');
                    lucide.createIcons();
                });

                // AJAX handler for deleting meal
                $('.delete-form').off('submit').on('submit', function(e) {
                    e.preventDefault();

                    const $form = $(this);
                    const mealName = $form.find('input[name="meal_name"]').val();

                    if (!confirm('Are you sure you want to delete ' + mealName + '?')) {
                        return;
                    }

                    const $row = $form.closest('tr');

                    $.ajax({
                        url: 'create.php',
                        method: 'POST',
                        data: {
                            delete_meal: 1,
                            meal_name: mealName
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $row.animate({opacity: 0}, 200, function() {
                                    $(this).remove();

                                    // Show empty message if no meals left
                                    if (response.mealsCount === 0) {
                                        const emptyRow = `
                                            <tr>
                                                <td colspan="6" class="py-8 text-center text-stone-400 empty-message">
                                                    <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
                                                    <p>No meals have been added yet</p>
                                                </td>
                                            </tr>
                                        `;
                                        $('.meals-table tbody').html(emptyRow);
                                        lucide.createIcons();
                                    }
                                });

                                showToast(response.message, 'success');
                            } else {
                                showToast('Error deleting meal', 'error');
                            }
                        },
                        error: function() {
                            showToast('Error deleting meal. Please try again.', 'error');
                        }
                    });
                });
            }

            // Initial binding
            bindMealEvents();

            // Close modal
            $('#close-modal').on('click', function() {
                $('#edit-modal').addClass('hidden');
            });

            // Close description modal
            $('#close-description-modal').on('click', function() {
                $('#description-modal').addClass('hidden');
            });

            // Close modal when clicking outside
            $('#edit-modal, #description-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).addClass('hidden');
                }
            });
        });
    </script>
    <script src="sort.js"></script>
</body>

</html>
