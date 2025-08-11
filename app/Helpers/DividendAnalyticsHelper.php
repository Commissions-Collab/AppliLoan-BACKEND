<?php 

namespace App\Helpers;

use App\Models\MemberAccount;

class DividendAnalyticsHelper {
    private static function getTotalDividendPaid() {
        return MemberAccount::sum('current_share_capital' * 0.05);
    }


}