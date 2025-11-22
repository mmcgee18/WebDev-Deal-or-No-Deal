<?php
session_start();

// Define the potential prize values
$prizes = [
    1, 5, 10, 50, 100, 200, 500, 1000,
    2000, 3000, 4000, 5000, 7500, 10000, 15000
];

// Initialize session variables if not set
if (!isset($_SESSION['case_values'])) {
    shuffle($prizes);
    $_SESSION['case_values'] = array_combine(range(1, 15), $prizes);
    $_SESSION['user_case'] = null; 
    $_SESSION['opened_cases'] = [];
    $_SESSION['banker_offers'] = [];
    $_SESSION['game_phase'] = 'select_case';
    $_SESSION['current_round'] = 1;
    $_SESSION['cases_opened_this_round'] = 0;
}

// Make sure all session variables are set
if (!isset($_SESSION['opened_cases'])) $_SESSION['opened_cases'] = [];
if (!isset($_SESSION['banker_offers'])) $_SESSION['banker_offers'] = [];
if (!isset($_SESSION['game_phase'])) $_SESSION['game_phase'] = 'select_case';
if (!isset($_SESSION['current_round'])) $_SESSION['current_round'] = 1;
if (!isset($_SESSION['cases_opened_this_round'])) $_SESSION['cases_opened_this_round'] = 0;

// Handle debug reset
if (isset($_POST['debug_reset'])) {
    session_destroy();
    header('Location: game.php');
    exit;
}

// Handle case selection
if (isset($_POST['select_case']) && $_SESSION['game_phase'] === 'select_case') {
    $selected_case = (int)$_POST['select_case'];
    if (array_key_exists($selected_case, $_SESSION['case_values'])) {
        $_SESSION['user_case'] = $selected_case;
        $_SESSION['game_phase'] = 'opening_cases';
        $_SESSION['current_round'] = 1;
        $_SESSION['cases_opened_this_round'] = 0;
    }
}

// Handle case opening
if (isset($_POST['open_case']) && $_SESSION['game_phase'] === 'opening_cases') {
    $case_to_open = (int)$_POST['open_case'];
    if (array_key_exists($case_to_open, $_SESSION['case_values']) && 
        $case_to_open !== $_SESSION['user_case'] &&
        !in_array($case_to_open, $_SESSION['opened_cases'])) {
        
        $_SESSION['opened_cases'][] = $case_to_open;
        $_SESSION['cases_opened_this_round']++;
        
        // Check if we've opened enough cases for this round
        $round_targets = [6, 4, 3, 2, 1]; // Cases to open each round
        
        $current_round_target = $round_targets[$_SESSION['current_round'] - 1] ?? 1;
        
        if ($_SESSION['cases_opened_this_round'] >= $current_round_target || count($_SESSION['opened_cases']) >= 14) {
            $_SESSION['game_phase'] = 'banker_offer';
            $_SESSION['cases_opened_this_round'] = 0;
            generateBankerOffer();
        }
    }
}

// Handle banker deal
if (isset($_POST['deal'])) {
    if ($_POST['deal'] === 'accept') {
        $_SESSION['game_phase'] = 'game_over';
        $_SESSION['final_offer'] = end($_SESSION['banker_offers']);
        $_SESSION['game_result'] = 'deal';
    } else {
        $_SESSION['game_phase'] = 'opening_cases';
        $_SESSION['current_round']++;
        $_SESSION['cases_opened_this_round'] = 0;
        
        // If we're past round 5 or all cases are opened, go to final offer
        if ($_SESSION['current_round'] > 5 || count($_SESSION['opened_cases']) >= 14) {
            $_SESSION['game_phase'] = 'final_offer';
            generateBankerOffer();
        }
    }
}

// Handle final decision
if (isset($_POST['final_decision'])) {
    if ($_POST['final_decision'] === 'deal') {
        $_SESSION['game_phase'] = 'game_over';
        $_SESSION['final_offer'] = end($_SESSION['banker_offers']);
        $_SESSION['game_result'] = 'deal';
    } else {
        $_SESSION['game_phase'] = 'game_over';
        $_SESSION['final_offer'] = $_SESSION['case_values'][$_SESSION['user_case']];
        $_SESSION['game_result'] = 'no_deal';
    }
}

// Reset game
if (isset($_POST['reset_game'])) {
    session_destroy();
    header('Location: game.php');
    exit;
}

// Function to generate banker offer
function generateBankerOffer() {
    $remaining_prizes = getRemainingPrizes();
    $average = array_sum($remaining_prizes) / count($remaining_prizes);
    
    // Banker's offer is a percentage of the average, increasing each round
    $round = $_SESSION['current_round'];
    $offer_percentages = [0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8];
    
    $percentage = $offer_percentages[min($round - 1, count($offer_percentages) - 1)];
    $offer = round($average * $percentage);
    
    // Ensure offer is reasonable
    $min_offer = min($remaining_prizes);
    $max_offer = max($remaining_prizes);
    $offer = max($min_offer, min($offer, $max_offer));
    
    $_SESSION['banker_offers'][] = $offer;
}

// Function to get remaining prizes
function getRemainingPrizes() {
    $remaining = [];
    foreach ($_SESSION['case_values'] as $case_num => $value) {
        if (!in_array($case_num, $_SESSION['opened_cases']) && $case_num !== $_SESSION['user_case']) {
            $remaining[] = $value;
        }
    }
    // Include user's case value in remaining for calculation
    if ($_SESSION['user_case']) {
        $remaining[] = $_SESSION['case_values'][$_SESSION['user_case']];
    }
    return $remaining;
}

$case_values = $_SESSION['case_values'];
$user_case_number = $_SESSION['user_case'];
$opened_cases = $_SESSION['opened_cases'];
$game_phase = $_SESSION['game_phase'];
$current_round = $_SESSION['current_round'];
$banker_offers = $_SESSION['banker_offers'];
$cases_opened_this_round = $_SESSION['cases_opened_this_round'];
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
    <style>
        /* Restore the beautiful original styling */
        .grid-item {
            background-color: silver;
            color: #000;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
            aspect-ratio: 1 / 1;
            position: relative;
        }
        
        .grid-item.user-selected {
            background-color: gold !important;
            border: 3px solid red;
            transform: scale(1.1);
        }
        
        .grid-item.opened {
            background-color: #666 !important;
            color: #999 !important;
            cursor: not-allowed;
        }
        
        .grid-item.opened::after {
            content: "$" attr(data-value);
            display: block;
            font-size: 0.8rem;
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            text-align: center;
        }
        
        .personal.user-case {
            background-color: rgba(255, 215, 0, 0.3);
            border: 3px solid gold;
            width: 150px;
            height: 150px;
            font-size: 1.5rem;
            color: white;
            border-radius: 10px;
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .status-message {
            color: white;
            font-size: 1.2rem;
            margin: 10px 0;
            text-align: center;
            min-height: 30px;
            text-shadow: 1px 1px 2px black;
        }
        
        .banker-offer {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border: 3px solid #b8860b;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        
        .banker-offer h2 {
            color: #8b4513;
            margin: 0 0 10px 0;
            font-size: 1.8rem;
        }
        
        .offer-amount {
            font-size: 3rem;
            font-weight: bold;
            color: #228b22;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .deal-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .deal-btn, .no-deal-btn {
            padding: 15px 30px;
            font-size: 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s;
            font-weight: bold;
        }
        
        .deal-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .no-deal-btn {
            background: linear-gradient(135deg, #f44336, #da190b);
            color: white;
        }
        
        .deal-btn:hover, .no-deal-btn:hover {
            transform: scale(1.05);
        }
        
        .game-over {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            color: white;
            margin: 20px 0;
        }
        
        .final-result {
            font-size: 2.5rem;
            color: gold;
            margin: 20px 0;
        }
        
        .reset-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            margin: 20px 0;
        }
        
        .debug-reset-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 0.9rem;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1000;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .debug-reset-btn:hover {
            opacity: 1;
            transform: scale(1.05);
        }
        
        .round-info {
            color: white;
            font-size: 1.1rem;
            margin: 10px 0;
            text-align: center;
        }
        
        .debug-info {
            position: fixed;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            z-index: 1000;
            max-width: 200px;
        }
        
        .grid-item:hover:not(.opened):not(.user-selected) {
            transform: scale(1.05);
            background-color: #fff;
        }
        
        .final-decision {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border: 3px solid #b8860b;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        
        .final-decision h2 {
            color: #8b4513;
            margin: 0 0 10px 0;
            font-size: 1.8rem;
        }
        
        .final-offer-amount {
            font-size: 3rem;
            font-weight: bold;
            color: #228b22;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            margin: 20px 0;
        }
        
        .your-case-value {
            font-size: 2rem;
            font-weight: bold;
            color: #dc143c;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <!-- Debug Reset Button - Always Available -->
    <form method="post" style="display: inline;">
        <button type="submit" name="debug_reset" class="debug-reset-btn" title="Reset Game Instantly">
            🔄 DEBUG RESET
        </button>
    </form>

    <!-- Debug Information -->
    <div class="debug-info">
        <strong>Debug Info:</strong><br>
        Phase: <?php echo $game_phase; ?><br>
        Round: <?php echo $current_round; ?><br>
        User Case: <?php echo $user_case_number ?: 'Not set'; ?><br>
        Opened: <?php echo count($opened_cases); ?>/14<br>
        This Round: <?php echo $cases_opened_this_round; ?><br>
        Banker Offers: <?php echo count($banker_offers); ?>
    </div>

    <div class="window">
        
        <div class="game-container">
            <div class="round-info">
                Round: <?php echo $current_round; ?> | 
                Opened: <?php echo count($opened_cases); ?>/14 cases
            </div>
            
            <div class="status-message" id="statusMessage">
                <?php
                switch ($game_phase) {
                    case 'select_case':
                        echo "Please select your personal case!";
                        break;
                    case 'opening_cases':
                        $round_targets = [6, 4, 3, 2, 1];
                        $current_target = $round_targets[$current_round - 1] ?? 1;
                        $remaining_this_round = $current_target - $cases_opened_this_round;
                        
                        // Don't ask for more cases than are actually available
                        $total_remaining = 15 - count($opened_cases) - 1; // -1 for user's case
                        $remaining_this_round = min($remaining_this_round, $total_remaining);
                        
                        if ($remaining_this_round > 0) {
                            echo "Round $current_round: Open $remaining_this_round more case" . ($remaining_this_round != 1 ? 's' : '');
                        } else {
                            // This should not happen, but just in case
                            $_SESSION['game_phase'] = 'banker_offer';
                            generateBankerOffer();
                            echo "The Banker is making an offer!";
                        }
                        break;
                    case 'banker_offer':
                        echo "The Banker is making an offer!";
                        break;
                    case 'final_offer':
                        echo "Final Decision Time!";
                        break;
                    case 'game_over':
                        echo "Game Over!";
                        break;
                }
                ?>
            </div>
            
            <?php if ($game_phase === 'banker_offer'): ?>
                <div class="banker-offer">
                    <h2>BANKER'S OFFER</h2>
                    <div class="offer-amount">$<?php echo number_format(end($banker_offers)); ?></div>
                    <div class="deal-buttons">
                        <form method="post">
                            <button type="submit" name="deal" value="accept" class="deal-btn">DEAL</button>
                            <button type="submit" name="deal" value="reject" class="no-deal-btn">NO DEAL</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($game_phase === 'final_offer'): ?>
                <div class="final-decision">
                    <h2>FINAL DECISION</h2>
                    <div class="final-offer-amount">
                        Banker's Final Offer: $<?php echo number_format(end($banker_offers)); ?>
                    </div>
                    <div class="your-case-value">
                        Your Case #<?php echo $user_case_number; ?> Value: ??? 
                    </div>
                    <div class="deal-buttons">
                        <form method="post">
                            <button type="submit" name="final_decision" value="deal" class="deal-btn">TAKE THE DEAL</button>
                            <button type="submit" name="final_decision" value="no_deal" class="no-deal-btn">KEEP MY CASE</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($game_phase === 'game_over'): ?>
                <div class="game-over">
                    <h2>GAME OVER</h2>
                    <div class="final-result">
                        <?php if ($_SESSION['game_result'] === 'deal'): ?>
                            You accepted: $<?php echo number_format($_SESSION['final_offer']); ?>
                        <?php else: ?>
                            Your case contained: $<?php echo number_format($_SESSION['final_offer']); ?>
                        <?php endif; ?>
                    </div>
                    <p>Your case #<?php echo $user_case_number; ?> had $<?php echo number_format($case_values[$user_case_number]); ?></p>
                    <form method="post">
                        <button type="submit" name="reset_game" class="reset-btn">PLAY AGAIN</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="grid-container">
                <?php foreach ($case_values as $case_num => $value): ?>
                    <?php 
                    $classes = ['grid-item'];
                    if ($user_case_number == $case_num) {
                        $classes[] = 'user-selected';
                    }
                    if (in_array($case_num, $opened_cases)) {
                        $classes[] = 'opened';
                    }
                    $class_string = implode(' ', $classes);
                    ?>
                    <div class="<?php echo $class_string; ?>" 
                         data-case-number="<?php echo $case_num; ?>"
                         data-value="<?php echo $value; ?>"
                         onclick="handleCaseClick(<?php echo $case_num; ?>)">
                        <?php echo $case_num; ?>
                    </div>
                <?php endforeach; ?>
            </div> 
        </div> 
        
        <div class="content">
            <div class="personal <?php echo $user_case_number ? 'user-case' : ''; ?>">
                <?php if ($user_case_number): ?>
                    Your Case: <?php echo $user_case_number; ?>
                <?php else: ?>
                    Select your case
                <?php endif; ?>
            </div>

            <div class="info left">
                <h2>Remaining Prizes</h2>
                <ol>
                    <?php
                    $remaining_prizes = getRemainingPrizes();
                    sort($remaining_prizes);
                    for ($i = 0; $i < min(8, count($remaining_prizes)); $i++): ?>
                        <li>$<?php echo number_format($remaining_prizes[$i]); ?></li>
                    <?php endfor; ?>
                </ol>
            </div>
            <div class="info right">
                <h2>Remaining Prizes</h2>
                <ol start="9">
                    <?php for ($i = 8; $i < count($remaining_prizes); $i++): ?>
                        <li>$<?php echo number_format($remaining_prizes[$i]); ?></li>
                    <?php endfor; ?>
                </ol>
            </div>
        </div>
    </div>

    <form id="caseForm" method="post" style="display: none;">
        <input type="hidden" name="select_case" id="selectCaseInput">
        <input type="hidden" name="open_case" id="openCaseInput">
    </form>

    <script>
        function handleCaseClick(caseNumber) {
            const caseElement = document.querySelector(`[data-case-number="${caseNumber}"]`);
            
            // If case is already opened, do nothing
            if (caseElement.classList.contains('opened')) {
                return;
            }
            
            <?php if ($game_phase === 'select_case'): ?>
                // Select as personal case
                document.getElementById('selectCaseInput').value = caseNumber;
                document.getElementById('caseForm').submit();
            <?php elseif ($game_phase === 'opening_cases'): ?>
                // Open the case (but not the user's personal case)
                if (caseNumber !== <?php echo $user_case_number ?: 'null'; ?>) {
                    document.getElementById('openCaseInput').value = caseNumber;
                    document.getElementById('caseForm').submit();
                } else {
                    alert("This is your personal case! You can't open it until the end.");
                }
            <?php endif; ?>
        }
    </script>
</body>
</html>