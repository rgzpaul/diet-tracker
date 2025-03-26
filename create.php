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
function calculateKcal($protein, $carbs, $fat) {
    return ($protein * 4) + ($carbs * 4) + ($fat * 9);
}

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
        $meals[] = [
            'name' => $name,
            'protein' => $protein,
            'carbs' => $carbs,
            'fat' => $fat,
            'color' => $color
        ];
        
        // Save the updated meals
        file_put_contents($mealsFile, json_encode($meals, JSON_PRETTY_PRINT));
        
        $message = "Meal added successfully!";
        $messageType = "success";
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . $messageType);
        exit;
    } else {
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
        foreach ($meals as $key => $meal) {
            if ($meal['name'] === $oldName) {
                $meals[$key] = [
                    'name' => $newName,
                    'protein' => $protein,
                    'carbs' => $carbs,
                    'fat' => $fat,
                    'color' => $color
                ];
                break;
            }
        }
        
        // Save the updated meals
        file_put_contents($mealsFile, json_encode($meals, JSON_PRETTY_PRINT));
        
        $message = "Meal updated successfully!";
        $messageType = "success";
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . $messageType);
        exit;
    } else {
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
usort($meals, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Meals - Meal Tracker</title>
    <!-- Manifest and Icons for PWA -->
    <link rel="manifest" href="./manifest.json">
    <link rel="icon" type="image/png" sizes="192x192" href="/train/icon.png">
    <meta name="theme-color" content="#1a73e8">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-purple-700">Manage Meals</h1>
        
        <!-- Message Display -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Add New Meal Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Meal</h2>
            
            <form method="post" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Meal Name</label>
                        <input type="text" id="name" name="name" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label for="protein" class="block text-sm font-medium text-gray-700 mb-1">P</label>
                            <input type="number" id="protein" name="protein" min="0" step="0.1" required value="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <div>
                            <label for="carbs" class="block text-sm font-medium text-gray-700 mb-1">C</label>
                            <input type="number" id="carbs" name="carbs" min="0" step="0.1" required value="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <div>
                            <label for="fat" class="block text-sm font-medium text-gray-700 mb-1">F</label>
                            <input type="number" id="fat" name="fat" min="0" step="0.1" required value="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Button Color</label>
                    <select id="color" name="color" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        <option value="blue">Blue</option>
                        <option value="brown">Brown</option>
                        <option value="green">Dark Green</option>
                        <option value="orange">Orange</option>
                    </select>
                </div>
                
                <div class="flex justify-between items-center pt-2">
                    <div class="text-sm kcal-preview text-gray-600">
                        Estimated calories: <span id="estimated-kcal">0</span> kcal
                    </div>
                    <button type="submit" name="add_meal" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Add Meal
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Existing Meals -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Existing Meals</h2>
            
            <?php if (empty($meals)): ?>
                <p class="text-gray-500 text-center py-4">No meals have been added yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-2 text-left">Meal Name</th>
                                <th class="border p-2 text-center">K</th>
                                <th class="border p-2 text-center">P</th>
                                <th class="border p-2 text-center">C</th>
                                <th class="border p-2 text-center">F</th>
                                <th class="border p-2 text-center">Color</th>
                                <th class="border p-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meals as $meal): ?>
                                <?php $mealKcal = calculateKcal($meal['protein'], $meal['carbs'], $meal['fat']); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="border p-2"><?php echo htmlspecialchars($meal['name']); ?></td>
                                    <td class="border p-2 text-center"><?php echo $mealKcal; ?></td>
                                    <td class="border p-2 text-center"><?php echo $meal['protein']; ?></td>
                                    <td class="border p-2 text-center"><?php echo $meal['carbs']; ?></td>
                                    <td class="border p-2 text-center"><?php echo $meal['fat']; ?></td>
                                    <td class="border p-2 text-center">
                                        <span class="inline-block w-6 h-6 rounded-full" style="background-color: <?php echo isset($meal['color']) ? $meal['color'] : 'blue'; ?>"></span>
                                    </td>
                                    <td class="border p-2 text-center">
                                        <div class="flex justify-center space-x-2">
                                            <button type="button" 
                                                class="edit-meal-btn px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
                                                data-name="<?php echo htmlspecialchars($meal['name']); ?>"
                                                data-protein="<?php echo $meal['protein']; ?>"
                                                data-carbs="<?php echo $meal['carbs']; ?>"
                                                data-fat="<?php echo $meal['fat']; ?>"
                                                data-color="<?php echo isset($meal['color']) ? $meal['color'] : 'blue'; ?>">
                                                Edit
                                            </button>
                                            
                                            <form method="post" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this meal?');">
                                                <input type="hidden" name="meal_name" value="<?php echo htmlspecialchars($meal['name']); ?>">
                                                <button type="submit" name="delete_meal" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                                    Delete
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
        <div id="edit-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Edit Meal</h3>
                
                <form method="post" class="space-y-4">
                    <input type="hidden" id="edit-old-name" name="old_name">
                    
                    <div>
                        <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-1">Meal Name</label>
                        <input type="text" id="edit-name" name="name" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label for="edit-protein" class="block text-sm font-medium text-gray-700 mb-1">P</label>
                            <input type="number" id="edit-protein" name="protein" min="0" step="0.1" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <div>
                            <label for="edit-carbs" class="block text-sm font-medium text-gray-700 mb-1">C</label>
                            <input type="number" id="edit-carbs" name="carbs" min="0" step="0.1" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <div>
                            <label for="edit-fat" class="block text-sm font-medium text-gray-700 mb-1">F</label>
                            <input type="number" id="edit-fat" name="fat" min="0" step="0.1" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>
                    
                    <div>
                        <label for="edit-color" class="block text-sm font-medium text-gray-700 mb-1">Button Color</label>
                        <select id="edit-color" name="color" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                            <option value="blue">Blue</option>
                            <option value="brown">Brown</option>
                            <option value="green">Dark Green</option>
                            <option value="orange">Orange</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-between items-center pt-2">
                        <div class="text-sm edit-kcal-preview text-gray-600">
                            Estimated calories: <span id="edit-estimated-kcal">0</span> kcal
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" id="close-modal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" name="update_meal" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update Meal
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Back to Home Link -->
        <div class="text-center mt-6">
            <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 mx-2 rounded-lg text-center">
                Daily Tracker
            </a>
            <a href="report.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 mx-2 rounded-lg text-center">
                Weekly Report
            </a>
        </div>
    </div>

    <script>
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
            $('#edit-protein, #edit-carbs, #edit-fat').on('input', updateEditEstimatedKcal);
            
            // Initialize KCAL preview
            updateEstimatedKcal();
            
            // Edit meal button click
            $('.edit-meal-btn').on('click', function() {
                const name = $(this).data('name');
                const protein = $(this).data('protein');
                const carbs = $(this).data('carbs');
                const fat = $(this).data('fat');
                const color = $(this).data('color');
                
                $('#edit-old-name').val(name);
                $('#edit-name').val(name);
                $('#edit-protein').val(protein);
                $('#edit-carbs').val(carbs);
                $('#edit-fat').val(fat);
                $('#edit-color').val(color);
                
                updateEditEstimatedKcal();
                
                $('#edit-modal').removeClass('hidden');
            });
            
            // Close modal
            $('#close-modal').on('click', function() {
                $('#edit-modal').addClass('hidden');
            });
            
            // Close modal when clicking outside
            $('#edit-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).addClass('hidden');
                }
            });
        });
    </script>
    <script src="sort.js"></script>
</body>
</html>