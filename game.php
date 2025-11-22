<?php
session_start();

// Define the potential prize values
$prizes = [
    1, 5, 10, 50, 100, 200, 500, 1000,
    2000, 3000, 4000, 5000, 7500, 10000, 15000
];

// Check if case values are already set in the session
if (!isset($_SESSION['case_values'])) {
    // If not, shuffle the prizes and assign them to cases 1 through 15
    shuffle($prizes);
    $_SESSION['case_values'] = array_combine(range(1, 15), $prizes);
    
    // Initialize the user's selected case number as null until they select one
    $_SESSION['user_case'] = Null; 
}

$case_values = $_SESSION['case_values'];
$user_case_number = $_SESSION['user_case'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <title>Deal or No Deal: Game</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="window">
        
        <div class="game-container">
            <div class="grid-container">
                <!-- PHP loop to generate grid items dynamically -->
                <?php foreach ($case_values as $case_num => $value): ?>
                    <?php 
                    // Add a class if this is the user's selected case to style it differently
                    $is_user_case = ($user_case_number == $case_num) ? ' user-selected' : '';
                    ?>
                    <div class="grid-item<?php echo $is_user_case; ?>" data-case-number="<?php echo $case_num; ?>">
                        <?php echo $case_num; ?>
                    </div>
                <?php endforeach; ?>
            </div> 
        </div> 
        
        <div class="content">
            <div class="personal">
                <?php if ($user_case_number): ?>
                    Your Case: <?php echo $user_case_number; ?>
                <?php else: ?>
                    Select your case
                <?php endif; ?>
            </div>

            <div class="info left">
                <h2>Prizes</h2>
                <ol>
                    <li>$1</li>
                    <li>$5</li>
                    <li>$10</li>
                    <li>$50</li>
                    <li>$100</li>
                    <li>$200</li>
                    <li>$500</li>
                    <li>$1000</li>
                </ol>
            </div>
            <div class="info right">
                <h2>Prizes</h2>
                <ol start="9">
                    <li>$2000</li>
                    <li>$3000</li>
                    <li>$4000</li>
                    <li>$5000</li>
                    <li>$7500</li>
                    <li>$10000</li>
                    <li>$15000</li>
                </ol>
            </div>
        
        </div>

    </div>

</body>

</html>