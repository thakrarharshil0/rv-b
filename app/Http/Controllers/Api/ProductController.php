<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('category') && ! in_array($request->category, ['shop', 'new-in', 'all'], true)) {
            $query->whereRaw('lower(category) = ?', [strtolower($request->category)]);
        }

        if ($request->filled('search')) {
            $q = strtolower($request->search);
            $query->where(function ($builder) use ($q) {
                $builder->whereRaw('lower(name) like ?', ["%{$q}%"])
                    ->orWhereRaw('lower(category) like ?', ["%{$q}%"]);
            });
        }

        if ($request->boolean('newOnly')) {
            $query->where('is_new', true);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->boolean('limited')) {
            $query->where('is_limited', true);
        }

        if ($request->filled('stock_limit')) {
            $query->where('quantity', '<=', $request->integer('stock_limit'));
        }

        $sort = $request->string('sort')->toString();
        if ($sort === 'price_asc') {
            $query->orderBy('price');
        } elseif ($sort === 'price_desc') {
            $query->orderByDesc('price');
        } elseif ($sort === 'newest') {
            $query->orderByDesc('created_at');
        } else {
            $query->orderBy('name');
        }

        $perPage = max(1, min(48, (int) $request->input('perPage', 24)));
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(function ($p) {
            return [
                'id' => (string) $p->id,
                'name' => $p->name,
                'category' => $p->category,
                'price' => (float) $p->price,
                'originalPrice' => $p->original_price !== null ? (float) $p->original_price : null,
                'quantity' => (int) $p->quantity,
                'image' => $p->image,
                'sku' => $p->sku,
                'description' => $p->description,
                'isNew' => (bool) $p->is_new,
                'isFeatured' => (bool) $p->is_featured,
                'isLimited' => (bool) $p->is_limited,
                'fit' => $p->fit,
                'material' => $p->material,
                'careInstructions' => $p->care_instructions,
                'sizes' => $p->sizes ?: [],
                'colors' => $p->colors ?: [],
                'features' => $p->features ?: [],
                'gallery' => $p->gallery ?: [],
                'ratingAverage' => (float) $p->rating_average,
                'reviewsCount' => (int) $p->reviews_count,
                'displayPrice' => '₹'.number_format((float) $p->price, 0),
                'originalDisplayPrice' => $p->original_price !== null ? '₹'.number_format((float) $p->original_price, 0) : null,
            ];
        });

        return response()->json([
            'items' => $items,
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(string $id)
    {
        $product = Product::with(['reviews.user' => fn ($query) => $query->select('id', 'name')])->find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json([
            'item' => [
                'id' => (string) $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
                'originalPrice' => $product->original_price !== null ? (float) $product->original_price : null,
                'quantity' => (int) $product->quantity,
                'image' => $product->image,
                'sku' => $product->sku,
                'description' => $product->description,
                'isNew' => (bool) $product->is_new,
                'isFeatured' => (bool) $product->is_featured,
                'isLimited' => (bool) $product->is_limited,
                'fit' => $product->fit,
                'material' => $product->material,
                'careInstructions' => $product->care_instructions,
                'sizes' => $product->sizes ?: [],
                'colors' => $product->colors ?: [],
                'features' => $product->features ?: [],
                'gallery' => $product->gallery ?: [],
                'ratingAverage' => (float) $product->rating_average,
                'reviewsCount' => (int) $product->reviews_count,
                'displayPrice' => '₹'.number_format((float) $product->price, 0),
                'originalDisplayPrice' => $product->original_price !== null ? '₹'.number_format((float) $product->original_price, 0) : null,
                'reviews' => $product->reviews->map(function ($review) {
                    return [
                        'id' => (string) $review->id,
                        'rating' => (int) $review->rating,
                        'title' => $review->title,
                        'comment' => $review->comment,
                        'user' => [
                            'id' => (string) $review->user->id,
                            'name' => $review->user->name,
                        ],
                        'createdAt' => $review->created_at,
                    ];
                })->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'originalPrice' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'sku' => 'nullable|string',
            'description' => 'nullable|string',
            'fit' => 'nullable|string|max:255',
            'material' => 'nullable|string|max:255',
            'careInstructions' => 'nullable|string',
            'sizes' => 'nullable|array',
            'colors' => 'nullable|array',
            'features' => 'nullable|array',
            'gallery' => 'nullable|array',
            'is_new' => 'nullable',
            'is_featured' => 'nullable',
            'is_limited' => 'nullable',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $imagePath = \Illuminate\Support\Facades\Storage::url($path);
        }

        $galleryPaths = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $path = $file->store('products', 'public');
                $galleryPaths[] = \Illuminate\Support\Facades\Storage::url($path);
            }
        }

        $product = Product::create([
            'name' => $data['name'],
            'category' => $data['category'],
            'price' => $data['price'],
            'original_price' => $data['originalPrice'] ?? null,
            'quantity' => $data['quantity'] ?? 0,
            'image' => $imagePath,
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'fit' => $data['fit'] ?? null,
            'material' => $data['material'] ?? null,
            'care_instructions' => $data['careInstructions'] ?? null,
            'sizes' => $data['sizes'] ?? [],
            'colors' => $data['colors'] ?? [],
            'features' => $data['features'] ?? [],
            'gallery' => $galleryPaths,
            'is_new' => $request->boolean('is_new'),
            'is_featured' => $request->boolean('is_featured'),
            'is_limited' => $request->boolean('is_limited'),
        ]);

        return response()->json(['item' => $product], 201);
    }

    public function update(Request $request, string $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'originalPrice' => 'nullable|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'sku' => 'nullable|string',
            'description' => 'nullable|string',
            'fit' => 'nullable|string|max:255',
            'material' => 'nullable|string|max:255',
            'careInstructions' => 'nullable|string',
            'sizes' => 'nullable|array',
            'colors' => 'nullable|array',
            'features' => 'nullable|array',
            'gallery' => 'nullable|array',
            'isNew' => 'nullable|string',
            'isFeatured' => 'nullable|string',
            'isLimited' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->image = \Illuminate\Support\Facades\Storage::url($path);
        }

        $galleryPaths = $product->gallery ?: [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $path = $file->store('products', 'public');
                $galleryPaths[] = \Illuminate\Support\Facades\Storage::url($path);
            }
        }

        $product->fill([
            'name' => $data['name'] ?? $product->name,
            'category' => $data['category'] ?? $product->category,
            'price' => $data['price'] ?? $product->price,
            'original_price' => array_key_exists('originalPrice', $data) ? $data['originalPrice'] : $product->original_price,
            'quantity' => $data['quantity'] ?? $product->quantity,
            'sku' => $data['sku'] ?? $product->sku,
            'description' => $data['description'] ?? $product->description,
            'fit' => $data['fit'] ?? $product->fit,
            'material' => $data['material'] ?? $product->material,
            'care_instructions' => $data['careInstructions'] ?? $product->care_instructions,
            'sizes' => $data['sizes'] ?? $product->sizes,
            'colors' => $data['colors'] ?? $product->colors,
            'features' => $data['features'] ?? $product->features,
            'gallery' => $galleryPaths,
            'is_new' => $request->has('is_new') ? $request->boolean('is_new') : $product->is_new,
            'is_featured' => $request->has('is_featured') ? $request->boolean('is_featured') : $product->is_featured,
            'is_limited' => $request->has('is_limited') ? $request->boolean('is_limited') : $product->is_limited,
        ]);

        $product->save();

        return response()->json(['item' => $product]);
    }

    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $deletedId = (string) $product->id;

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }

    public function dashboardStats(Request $request)
    {
        $startDate = $request->query('startDate') ?: $request->query('from');
        $endDate = $request->query('endDate') ?: $request->query('to');

        $ordersQuery = Order::query();
        if ($startDate && $endDate) {
            $ordersQuery->whereBetween('created_at', [
                date('Y-m-d 00:00:00', strtotime($startDate)),
                date('Y-m-d 23:59:59', strtotime($endDate))
            ]);
        } elseif ($startDate) {
            $ordersQuery->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
        }

        $pendingOrders = (clone $ordersQuery)->where('status', 'pending')->count();
        $confirmedOrders = (clone $ordersQuery)->where('status', 'confirmed')->count();
        $packagingOrders = (clone $ordersQuery)->where('status', 'packaging')->count();
        $outForDeliveryOrders = (clone $ordersQuery)->where('status', 'delivery')->count();
        $deliveredOrders = (clone $ordersQuery)->where('status', 'delivered')->count();
        $canceledOrders = (clone $ordersQuery)->where('status', 'canceled')->count();
        $returnedOrders = (clone $ordersQuery)->where('status', 'returned')->count();
        $failedOrders = (clone $ordersQuery)->where('status', 'failed')->count();
        $totalSales = (float) (clone $ordersQuery)->where('status', 'delivered')->sum('total');
        $totalOrders = (clone $ordersQuery)->count();

        return response()->json([
            'totalProducts' => Product::count(),
            'totalOrders' => $totalOrders,
            'totalCustomers' => User::where('role', 'customer')->count(),
            'pendingOrders' => $pendingOrders,
            'confirmedOrders' => $confirmedOrders,
            'packagingOrders' => $packagingOrders,
            'outForDeliveryOrders' => $outForDeliveryOrders,
            'deliveredOrders' => $deliveredOrders,
            'canceledOrders' => $canceledOrders,
            'returnedOrders' => $returnedOrders,
            'failedOrders' => $failedOrders,
            'totalSales' => $totalSales,
        ]);
    }

    public function salesAnalytics(Request $request)
    {
        $timeframe = $request->query('timeframe', '7D'); // 7D, 30D, 6M, 1Y

        $now = now();
        $chart = [];

        if ($timeframe === '7D') {
            for ($i = 6; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i);
                $dayStart = $day->copy()->startOfDay();
                $dayEnd = $day->copy()->endOfDay();
                $prevDayStart = $day->copy()->subDays(7)->startOfDay();
                $prevDayEnd = $day->copy()->subDays(7)->endOfDay();

                $sales = (float) Order::where('status', 'delivered')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('total');

                $prevSales = (float) Order::where('status', 'delivered')
                    ->whereBetween('created_at', [$prevDayStart, $prevDayEnd])
                    ->sum('total');

                $orderCount = Order::whereBetween('created_at', [$dayStart, $dayEnd])->count();

                $chart[] = [
                    'name' => $day->format('D'),
                    'date' => $day->format('d M'),
                    'sales' => $sales,
                    'prev' => $prevSales,
                    'orders' => $orderCount,
                ];
            }
        } elseif ($timeframe === '30D') {
            for ($i = 29; $i >= 0; $i -= 3) {
                $day = $now->copy()->subDays($i);
                $periodStart = $day->copy()->subDays(2)->startOfDay();
                $periodEnd = $day->copy()->endOfDay();

                $sales = (float) Order::where('status', 'delivered')
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->sum('total');

                $prevSales = (float) Order::where('status', 'delivered')
                    ->whereBetween('created_at', [$periodStart->copy()->subDays(30), $periodEnd->copy()->subDays(30)])
                    ->sum('total');

                $orderCount = Order::whereBetween('created_at', [$periodStart, $periodEnd])->count();

                $chart[] = [
                    'name' => $day->format('d M'),
                    'date' => $day->format('d M'),
                    'sales' => $sales,
                    'prev' => $prevSales,
                    'orders' => $orderCount,
                ];
            }
        } elseif ($timeframe === '6M') {
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $mStart = $month->copy()->startOfMonth();
                $mEnd = $month->copy()->endOfMonth();

                $sales = (float) Order::where('status', 'delivered')
                    ->whereBetween('created_at', [$mStart, $mEnd])
                    ->sum('total');

                $prevSales = (float) Order::where('status', 'delivered')
                    ->whereBetween('created_at', [$mStart->copy()->subYear(), $mEnd->copy()->subYear()])
                    ->sum('total');

                $orderCount = Order::whereBetween('created_at', [$mStart, $mEnd])->count();

                $chart[] = [
                    'name' => $month->format('M'),
                    'date' => $month->format('M Y'),
                    'sales' => $sales,
                    'prev' => $prevSales,
                    'orders' => $orderCount,
                ];
            }
        } else { // 1Y
            for ($i = 11; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $mStart = $month->copy()->startOfMonth();
                $mEnd = $month->copy()->endOfMonth();

                $sales = (float) Order::where('status', 'delivered')
                    ->whereBetween('created_at', [$mStart, $mEnd])
                    ->sum('total');

                $prevSales = (float) Order::where('status', 'delivered')
                    ->whereBetween('created_at', [$mStart->copy()->subYear(), $mEnd->copy()->subYear()])
                    ->sum('total');

                $orderCount = Order::whereBetween('created_at', [$mStart, $mEnd])->count();

                $chart[] = [
                    'name' => $month->format('M'),
                    'date' => $month->format('M Y'),
                    'sales' => $sales,
                    'prev' => $prevSales,
                    'orders' => $orderCount,
                ];
            }
        }

        $totalSales = (float) Order::where('status', 'delivered')->sum('total');
        $totalOrders = Order::count();
        $deliveredOrders = Order::where('status', 'delivered')->count();
        $avgOrderValue = $deliveredOrders > 0 ? round($totalSales / $deliveredOrders, 2) : 0;

        $recentDelivered = Order::where('status', 'delivered')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'chart' => $chart,
            'summary' => [
                'totalSales' => $totalSales,
                'totalOrders' => $totalOrders,
                'deliveredOrders' => $deliveredOrders,
                'avgOrderValue' => $avgOrderValue,
            ],
            'recent' => $recentDelivered,
        ]);
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $imported = 0;
        $failed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            try {
                // Basic mapping based on expected CSV structure
                // 0: name, 1: sku, 2: category, 3: price, 4: original_price, 5: quantity, 6: description
                if (empty($row[0])) continue;

                Product::create([
                    'name' => $row[0],
                    'sku' => $row[1] ?? 'SKU-'.uniqid(),
                    'category' => $row[2] ?? 'Uncategorized',
                    'price' => (float)($row[3] ?? 0),
                    'original_price' => !empty($row[4]) ? (float)$row[4] : null,
                    'quantity' => (int)($row[5] ?? 0),
                    'description' => $row[6] ?? '',
                    'image' => $row[7] ?? null,
                    'sizes' => [],
                    'colors' => [],
                    'features' => [],
                    'gallery' => [],
                ]);
                $imported++;
            } catch (\Exception $e) {
                \Log::error("Bulk import failed for row: " . json_encode($row) . " Error: " . $e->getMessage());
                $failed++;
            }
        }

        fclose($handle);

        return response()->json([
            'message' => 'Bulk import completed',
            'imported' => $imported,
            'failed' => $failed,
        ]);
    }
}
