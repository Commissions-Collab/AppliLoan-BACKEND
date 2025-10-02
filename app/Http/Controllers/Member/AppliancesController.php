<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppliancesController extends Controller
{
    public function index()
    {
        try {
            $products = Product::with(['category:id,name'])
                ->where('status', 'active')
                ->select(['id', 'category_id', 'name', 'description', 'unit', 'price', 'stock_quantity', 'image'])
                ->latest()
                ->paginate(25);

            return response()->json([
                'success' => true,
                'products' => $products
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function passApplication(Request $request)
    {
        try {
            $user = Auth::user();
            $member = $user->member;

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member profile not found'
                ], 404);
            }

            $query = LoanApplication::with(['product:id,name'])
                ->where('user_id', $user->id);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            switch ($request->input('sort_by', 'date_desc')) {
                case 'name_asc':
                    $query->join('products', 'loan_applications.product_id', '=', 'products.id')
                        ->orderBy('products.name', 'asc')
                        ->select([
                            'loan_applications.id',
                            'loan_applications.product_id',
                            'loan_applications.application_date',
                            'loan_applications.status'
                        ]);
                    break;
                case 'name_desc':
                    $query->join('products', 'loan_applications.product_id', '=', 'products.id')
                        ->orderBy('products.name', 'desc')
                        ->select([
                            'loan_applications.id',
                            'loan_applications.product_id',
                            'loan_applications.application_date',
                            'loan_applications.status'
                        ]);
                    break;
                case 'date_asc':
                    $query->orderBy('application_date', 'asc');
                    break;
                case 'date_desc':
                default:
                    $query->orderBy('application_date', 'desc');
                    break;
            }

            $pastApplication = $query
                ->paginate(20);

            return response()->json([
                'success' => true,
                'past' => $pastApplication
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch past application list',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
