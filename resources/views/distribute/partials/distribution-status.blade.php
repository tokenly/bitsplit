<?php
if($distro->complete == 1){
    echo '<span class="text-success">Complete</span>';
}
else{
    if ($distro->isOnchainDistribution()) {
        switch($distro->stage){
            case 0:
                echo '<span class="text-warning">Initializing</span>';
                break;
            case 1:
                echo '<span class="text-warning">Collecting Tokens</span>';
                break;
            case 2:
                echo '<span class="text-warning">Collecting Fuel</span>';
                break;
            case 3:
                echo '<span class="text-info">Priming Inputs</span>';
                break;
            case 4:
                echo '<span class="text-info">Preparing Transactions</span>';
                break;
            case 5:
                echo '<span class="text-info">Broadcasting Transactions</span>';
                break;
            case 6:
                echo '<span class="text-info">Confirming Broadcasts</span>';
                break;
            case 7:
                echo '<span class="text-success">Performing Cleanup</span>';
                break;
            case 8:
                echo '<span class="text-success">Finalizing Cleanup</span>';
                break;
            default:
                echo '(unknown)';
                break;
        
        }
    }
    if ($distro->isOffchainDistribution()) {
        switch($distro->stage){
            case 0:
                echo '<span class="text-warning">Initializing</span>';
                break;
            case 1:
                // "1": "EnsureTokens"
                echo '<span class="text-warning">Checking Token Balances</span>';
                break;
            case 2:
                // "2": "AllocatePromises"
                echo '<span class="text-info">Allocating Tokens to Recipients</span>';
                break;
            case 3:
                // "3": "DistributePromises"
                echo '<span class="text-info">Distributing Tokens</span>';
                break;
            case 4:
                // "4": "Complete"
                echo '<span class="text-success">Finalizing Cleanup</span>';
                break;
            default:
                echo '(unknown)';
                break;
        
        }
    }
    if($distro->hold == 1){
        echo ' <strong class="text-danger">HOLD</strong>';
    }
}
?>