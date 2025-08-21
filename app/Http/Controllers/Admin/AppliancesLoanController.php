<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use Illuminate\Http\Request;

class AppliancesLoanController extends Controller
{
    public function index() 
    {
        $applications = LoanApplication::with('member:id,full_name', 'product:id,name', 'loan:id,loan_application_id,loan_number')
            ->select(['id', 'member_id', 'product_id', 'status'])
            ->whereHas('loan')
            ->latest()
            ->paginate(25);

        $formatted = $applications->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'loan_number' => optional($item->loan)->loan_number,
                'member_name' => optional($item->member)->full_name,
                'product_name' => optional($item->product)->name,
                'status' => $item->status
            ];
        });

         $applications->setCollection($formatted);

        return response()->json([
            'loan_applications' => $applications
        ]);
    }
}
