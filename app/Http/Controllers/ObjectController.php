<?php

namespace App\Http\Controllers;

use App\Models\MasterObjek;
use App\Models\MasterLokasiObjek;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class ObjectController extends Controller
{
    public function getObjectRatings(Request $request)
    {
        $user = JWTAutH::parseToken()->authenticate();

        if ($user) {
            $ratings = Rating::where('master_objek_id', $request->master_objek_id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'ratings');

            return response()->json([
                'status' => 200,
                'message' => 'Successfully Get Rating Objects',
                'pagination' => [
                    'current_page' => $ratings->currentPage(),
                    'per_page' => $ratings->perPage(),
                    'total' => $ratings->total(),
                    'last_page' => $ratings->lastPage(),
                    'next_page_url' => $ratings->nextPageUrl(), // Add this
                    'prev_page_url' => $ratings->previousPageUrl(),
                ],
                'ratings' => $ratings->items(),
            ]);
        }

        return response()->json(['status' => 200, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function getAllObjects(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user) {
            $query = MasterObjek::withTransactionCount()
                ->withAvg('ratings', 'rating_star')
                ->with(['lokasi', 'opd', 'retribusi', 'rincianRetribusi', 'satuan', 'tpj', 'statusObjek', 'typeObject', 'currency'])
                ->orderBy('id', 'desc');

            // Optional filters
            if ($request->has('master_opd_id')) {
                $query->where('master_opd_id', $request->master_opd_id);
            }
            if ($request->has('master_lokasi_id')) {
                $query->where('master_lokasi_id', $request->master_lokasi_id);
            }
            if ($request->has('master_retribusi_id')) {
                $query->where('master_retribusi_id', $request->master_retribusi_id);
            }
            if ($request->has('master_status_objek_id')) {
                $query->where('master_status_objek_id', $request->master_status_objek_id);
            }
            if ($request->has('master_type_object_id')) {
                $query->where('master_type_object_id', $request->master_type_object_id);
            }

            $perPage = $request->input('per_page', 8);
            $objects = $query->paginate($perPage, ['*'], 'objects');

            return response()->json([
                'status' => 200,
                'message' => 'Successfully Get Objects',
                'pagination' => [
                    'current_page' => $objects->currentPage(),
                    'per_page' => $objects->perPage(),
                    'total' => $objects->total(),
                    'last_page' => $objects->lastPage(),
                    'next_page_url' => $objects->nextPageUrl(),
                    'prev_page_url' => $objects->previousPageUrl(),
                ],
                'objects' => $objects->items(),
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function getMasterDataForForms(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user) {
            // Cache for 1 hour since master data rarely changes
            $filterOptions = \Cache::remember('master_data_forms', 3600, function () {
                return [
                    'opd' => \DB::table('master_opd')
                        ->select('id', 'nama_opd')
                        ->orderBy('nama_opd', 'asc')
                        ->get(),
                    'lokasi' => MasterLokasiObjek::with(['masterOpd' => function ($query) {
                        $query->select('id', 'nama_opd');
                    }])
                        ->select('id', 'nama_lokasi', 'master_opd_id')
                        ->orderBy('nama_lokasi', 'asc')
                        ->get(),
                    'retribusi' => \DB::table('master_retribusi')
                        ->select('id', 'nama_retribusi')
                        ->orderBy('nama_retribusi', 'asc')
                        ->get(),
                    'rincian_retribusi' => \DB::table('master_rincian_retribusi')
                        ->select('id', 'rincian_retribusi', 'master_tipe_retribusi_id')
                        ->orderBy('rincian_retribusi', 'asc')
                        ->get(),
                    'sub_rincian_retribusi' => \DB::table('master_sub_rincian_retribusi')
                        ->select('id', 'nama_sub_rincian_retribusi', 'master_rincian_retribusi_id')
                        ->orderBy('nama_sub_rincian_retribusi', 'asc')
                        ->get(),
                    'status_objek' => \DB::table('master_status_objek')
                        ->select('id', 'status_objek')
                        ->orderBy('status_objek', 'asc')
                        ->get(),
                    'type_objek' => \DB::table('master_type_object')
                        ->select('id', 'type')
                        ->orderBy('type', 'asc')
                        ->get(),
                    'currency' => \DB::table('master_currencies')
                        ->select('id', 'currency_name', 'currency')
                        ->orderBy('currency_name', 'asc')
                        ->get(),
                    'role' => \DB::table('master_role')
                        ->select('id', 'nama_role')
                        ->orderBy('nama_role', 'asc')
                        ->get(),
                    'rules' => \DB::table('master_rules')
                        ->select('id', 'nama_peraturan')
                        ->orderBy('nama_peraturan', 'asc')
                        ->get(),
                    'satuan' => \DB::table('master_satuan')
                        ->select('id', 'nama_satuan')
                        ->orderBy('nama_satuan', 'asc')
                        ->get(),
                    'tpj' => \DB::table('master_tpj')
                        ->select('id', 'nama_tpj')
                        ->orderBy('nama_tpj', 'asc')
                        ->get(),
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => 'Successfully Get Master Data',
                'data' => $filterOptions,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function getObjectByLocation(Request $request)
    {
        $user = JWTAutH::parseToken()->authenticate();

        if ($user) {
            $query = MasterObjek::where('master_lokasi_id', $request->lokasi_id)
                ->where('master_retribusi_id', $request->retribusi_id)
                ->where('master_type_object_id', '!=', 3)->where('master_type_object_id', '!=', 5)
                ->where('master_status_objek_id', 1)
                ->withTransactionCount()
                ->withAvg('ratings', 'rating_star')
                ->with(['lokasi', 'opd', 'retribusi', 'rincianRetribusi', 'satuan', 'tpj', 'statusObjek'])
                ->orderBy('id', 'asc');

            // Add opd_id filter if provided
            if ($request->has('opd_id')) {
                $query->where('master_opd_id', $request->opd_id);
            }

            $objects = $query->paginate($perPage = 8, $columns = ['*'], $pageName = 'objects_location');

            return response()->json([
                'status' => 200,
                'message' => 'Successfully Get Objects',
                'pagination' => [
                    'current_page' => $objects->currentPage(),
                    'per_page' => $objects->perPage(),
                    'total' => $objects->total(),
                    'last_page' => $objects->lastPage(),
                    'next_page_url' => $objects->nextPageUrl(),
                    'prev_page_url' => $objects->previousPageUrl(),
                ],
                'objects' => $objects->items(),
            ]);
        }

        return response()->json(['status' => 200, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function getObjectPenetapanKhusus(Request $request)
    {
        $user = JWTAutH::parseToken()->authenticate();

        if ($user) {
            $query = MasterObjek::where('master_lokasi_id', $request->lokasi_id)
                ->where('master_retribusi_id', $request->retribusi_id)
                ->where(function ($q) {
                    $q->where('master_type_object_id', 5)
                        ->orWhere('master_type_object_id', 13);
                })
                ->withTransactionCount()
                ->withAvg('ratings', 'rating_star')
                ->with(['lokasi', 'opd', 'retribusi', 'rincianRetribusi', 'satuan', 'tpj', 'statusObjek'])
                ->orderBy('id', 'asc');

            // Add opd_id filter if provided
            if ($request->has('opd_id')) {
                $query->where('master_opd_id', $request->opd_id);
            }

            $objects = $query->paginate($perPage = 8, $columns = ['*'], $pageName = 'objects_location');

            return response()->json([
                'status' => 200,
                'message' => 'Successfully Get Penetapan Khusus Objects',
                'pagination' => [
                    'current_page' => $objects->currentPage(),
                    'per_page' => $objects->perPage(),
                    'total' => $objects->total(),
                    'last_page' => $objects->lastPage(),
                    'next_page_url' => $objects->nextPageUrl(),
                    'prev_page_url' => $objects->previousPageUrl(),
                ],
                'objects' => $objects->items(),
            ]);
        }

        return response()->json(['status' => 200, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function searchObject(Request $request): JsonResponse
    {
        $user = JWTAutH::parseToken()->authenticate();
        if ($user) {
            // code...
            try {
                // Get search term from request
                $searchTerm = $request->input('search', '');

                // Start building the query with eager loading
                $query = MasterObjek::with([
                    'lokasi',
                    'opd',
                    'retribusi',
                    'rincianRetribusi',
                    'satuan',
                    'tpj',
                    'statusObjek',
                    'ratings',
                ])
                    ->where('master_type_object_id', '!=', 3)
                    ->where('master_type_object_id', '!=', 5)
                    ->where('master_retribusi_id', $request->retribusi_id)
                    ->where('master_lokasi_id', $request->lokasi_id)->where('master_status_objek_id', 1);
                if (! empty($searchTerm)) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('uraian_objek', 'like', "%{$searchTerm}%")
                            ->orWhere('rincian_objek', 'like', "%{$searchTerm}%")
                            ->orWhere('sub_rincian_objek', 'like', "%{$searchTerm}%")
                            ->orWhere('sub_rincian_retribusi', 'like', "%{$searchTerm}%")
                            ->orWhere('required_document', 'like', "%{$searchTerm}%");
                    })->where('master_type_object_id', '!=', 3)->where('master_retribusi_id', $request->retribusi_id)->where('master_lokasi_id', $request->lokasi_id)->where('master_status_objek_id', 1);
                }

                // Order by latest first and paginate results
                $objects = $query->paginate($perPage = 8, $columns = ['*'], $pageName = 'objects_search');

                // Return JSON response
                return response()->json([
                    'status' => 200,
                    'message' => 'Search completed successfully',
                    'data' => $objects->items(),
                    'pagination' => [
                        'current_page' => $objects->currentPage(),
                        'per_page' => $objects->perPage(),
                        'total' => $objects->total(),
                        'last_page' => $objects->lastPage(),
                        'next_page_url' => $objects->nextPageUrl(), // Add this
                        'prev_page_url' => $objects->previousPageUrl(),
                    ],
                    'search_term' => $searchTerm,
                ]);
            } catch (\Exception $e) {
                // Handle exceptions
                return response()->json([
                    'status' => 200,
                    'message' => 'Failed to perform search',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
        }
    }

    public function searchObjectPenetapanKhusus(Request $request): JsonResponse
    {
        $user = JWTAutH::parseToken()->authenticate();
        if ($user) {
            // code...
            try {
                // Get search term from request
                $searchTerm = $request->input('search', '');

                // Start building the query with eager loading
                $query = MasterObjek::with([
                    'lokasi',
                    'opd',
                    'retribusi',
                    'rincianRetribusi',
                    'satuan',
                    'tpj',
                    'statusObjek',
                    'ratings',
                ])
                    ->where('master_retribusi_id', $request->retribusi_id)
                    ->where('master_lokasi_id', $request->lokasi_id)
                    ->where('master_type_object_id', 5);
                if (! empty($searchTerm)) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('uraian_objek', 'like', "%{$searchTerm}%")
                            ->orWhere('rincian_objek', 'like', "%{$searchTerm}%")
                            ->orWhere('sub_rincian_objek', 'like', "%{$searchTerm}%")
                            ->orWhere('sub_rincian_retribusi', 'like', "%{$searchTerm}%")
                            ->orWhere('required_document', 'like', "%{$searchTerm}%");
                    })->where('master_retribusi_id', $request->retribusi_id)->where('master_lokasi_id', $request->lokasi_id)->where('master_type_object_id', 5);
                }

                // Order by latest first and paginate results
                $objects = $query->paginate($perPage = 8, $columns = ['*'], $pageName = 'objects_search');

                // Return JSON response
                return response()->json([
                    'status' => 200,
                    'message' => 'Search completed successfully',
                    'data' => $objects->items(),
                    'pagination' => [
                        'current_page' => $objects->currentPage(),
                        'per_page' => $objects->perPage(),
                        'total' => $objects->total(),
                        'last_page' => $objects->lastPage(),
                        'next_page_url' => $objects->nextPageUrl(), // Add this
                        'prev_page_url' => $objects->previousPageUrl(),
                    ],
                    'search_term' => $searchTerm,
                ]);
            } catch (\Exception $e) {
                // Handle exceptions
                return response()->json([
                    'status' => 200,
                    'message' => 'Failed to perform search',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
        }
    }

    public function createObject(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => "You're not authenticated",
            ]);
        }

        try {
            // Validate request
            $validated = $request->validate([
                'master_opd_id' => 'required|integer|exists:master_opd,id',
                'master_lokasi_id' => 'required|integer|exists:master_lokasi_objek,id',
                'uraian_objek' => 'required|string|max:255',
                'master_retribusi_id' => 'required|integer|exists:master_retribusi,id',
                'master_rincian_retribusi_id' => 'required|integer|exists:master_rincian_retribusi,id',
                'sub_rincian_retribusi' => 'nullable|string|max:255',
                'rincian_objek' => 'required|string|max:255',
                'sub_rincian_objek' => 'required|string|max:255',
                'master_role_id' => 'nullable|integer|exists:master_role,id',
                'master_satuan_id' => 'required|integer|exists:master_satuan,id',
                'master_rules_id' => 'required|integer|exists:master_rules,id',
                'master_tpj_id' => 'required|integer|exists:master_tpj,id',
                'harga_objek' => 'required|numeric|min:0',
                'harga_objek_asing' => 'nullable|numeric|min:0',
                'master_status_objek_id' => 'required|integer|exists:master_status_objek,id',
                'required_document' => 'nullable|string',
                'master_type_object_id' => 'required|integer|exists:master_type_object,id',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'master_currency_id' => 'required|integer|exists:master_currencies,id',
            ]);

            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('objects', 'public');
                    $imagePaths[] = $path;
                }
                $validated['images'] = json_encode($imagePaths);
            }

            // Create object
            $createdObject = MasterObjek::create($validated);

            // Re-query from database to apply $casts properly
            $object = MasterObjek::with([
                'lokasi',
                'rules',
                'opd',
                'retribusi',
                'rincianRetribusi',
                'satuan',
                'tpj',
                'statusObjek',
                'typeObject',
                'currency',
            ])->find($createdObject->id);

            return response()->json([
                'status' => 200,
                'message' => 'Object created successfully',
                'data' => $object,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to create object',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateObject(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => "You're not authenticated",
            ]);
        }

        try {
            // Find object
            $object = MasterObjek::find($id);

            if (! $object) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Object not found',
                ], 404);
            }

            // Log request data for debugging
            \Log::info('Update Object Request', [
                'id' => $id,
                'all_data' => $request->all(),
                'has_files' => $request->hasFile('images'),
            ]);

            // Validate request
            $validated = $request->validate([
                'master_opd_id' => 'sometimes|integer|exists:master_opd,id',
                'master_lokasi_id' => 'sometimes|integer|exists:master_lokasi_objek,id',
                'uraian_objek' => 'sometimes|string|max:255',
                'master_retribusi_id' => 'sometimes|integer|exists:master_retribusi,id',
                'master_rincian_retribusi_id' => 'sometimes|integer|exists:master_rincian_retribusi,id',
                'sub_rincian_retribusi' => 'nullable|string|max:255',
                'rincian_objek' => 'sometimes|string|max:255',
                'sub_rincian_objek' => 'sometimes|string|max:255',
                'master_role_id' => 'nullable|integer|exists:master_role,id',
                'master_satuan_id' => 'sometimes|integer|exists:master_satuan,id',
                'master_rules_id' => 'sometimes|integer|exists:master_rules,id',
                'master_tpj_id' => 'sometimes|integer|exists:master_tpj,id',
                'harga_objek' => 'sometimes|numeric|min:0',
                'harga_objek_asing' => 'nullable|numeric|min:0',
                'master_status_objek_id' => 'sometimes|integer|exists:master_status_objek,id',
                'required_document' => 'nullable|string',
                'master_type_object_id' => 'sometimes|integer|exists:master_type_object,id',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'deleted_images' => 'nullable|array',
                'deleted_images.*' => 'string',
                'master_currency_id' => 'sometimes|integer|exists:master_currencies,id',
            ]);

            // Remove images.* and deleted_images from validated (they're for file handling only)
            unset($validated['images']);
            unset($validated['deleted_images']);

            // Get existing images
            $existingImages = [];
            if ($object->images) {
                $existingImages = json_decode($object->images, true);
                if (!is_array($existingImages)) {
                    $existingImages = [];
                }
            }

            // Handle deletion of specific images
            if ($request->has('deleted_images') && is_array($request->deleted_images)) {
                \Log::info('Deleting specific images', ['deleted_images' => $request->deleted_images]);

                foreach ($request->deleted_images as $imageToDelete) {
                    // Security: Only delete if image belongs to this object
                    if (!in_array($imageToDelete, $existingImages)) {
                        \Log::warning('Attempted to delete image not belonging to object', [
                            'object_id' => $id,
                            'image_path' => $imageToDelete
                        ]);
                        continue;
                    }

                    // Remove from storage
                    if (Storage::disk('public')->exists($imageToDelete)) {
                        Storage::disk('public')->delete($imageToDelete);
                        \Log::info('Image deleted from storage', ['path' => $imageToDelete]);
                    }

                    // Remove from existing images array
                    $existingImages = array_values(array_filter($existingImages, function ($img) use ($imageToDelete) {
                        return $img !== $imageToDelete;
                    }));
                }
            }

            // Handle new image uploads - append to existing images
            if ($request->hasFile('images')) {
                \Log::info('Has images to upload', ['count' => count($request->file('images'))]);

                // Upload new images and append to existing
                foreach ($request->file('images') as $image) {
                    $path = $image->store('objects', 'public');
                    $existingImages[] = $path;
                    \Log::info('Image uploaded', ['path' => $path]);
                }
            }

            // Update images in validated data
            if (!empty($existingImages)) {
                $validated['images'] = json_encode(array_values($existingImages));
            } else {
                $validated['images'] = null;
            }

            // Log validated data
            \Log::info('Validated Data', $validated);

            // Update object
            $updated = $object->update($validated);

            // Log update result
            \Log::info('Update Result', [
                'success' => $updated,
                'object_id' => $object->id,
                'updated_data' => $object->fresh()->toArray(),
            ]);

            // Reload relationships
            $object->load([
                'lokasi',
                'rules',
                'opd',
                'retribusi',
                'rincianRetribusi',
                'satuan',
                'tpj',
                'statusObjek',
                'typeObject',
                'currency',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Object updated successfully',
                'data' => $object,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update object',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteObject($id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => "You're not authenticated",
            ]);
        }

        try {
            // Find object
            $object = MasterObjek::find($id);

            if (! $object) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Object not found',
                ], 404);
            }

            // Delete images if exists
            if ($object->images) {
                $images = json_decode($object->images, true);
                if (is_array($images)) {
                    foreach ($images as $image) {
                        Storage::disk('public')->delete($image);
                    }
                }
            }

            // Delete object
            $object->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Object deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete object',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteObjectImages(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => "You're not authenticated",
            ]);
        }

        try {
            // Find object
            $object = MasterObjek::find($id);

            if (! $object) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Object not found',
                ], 404);
            }

            // Delete all images
            if ($object->images) {
                $images = json_decode($object->images, true);
                if (is_array($images)) {
                    foreach ($images as $image) {
                        Storage::disk('public')->delete($image);
                    }
                }
            }

            // Clear images field
            $object->update(['images' => null]);

            return response()->json([
                'status' => 200,
                'message' => 'Object images deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete images',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getObjectById($id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => "You're not authenticated",
            ]);
        }

        try {
            // Find object with relationships
            $object = MasterObjek::with([
                'lokasi',
                'rules',
                'opd',
                'retribusi',
                'rincianRetribusi',
                'satuan',
                'tpj',
                'statusObjek',
                'ratings',
                'typeObject',
                'currency',
            ])
                ->withAvg('ratings', 'rating_star')
                ->find($id);

            if (! $object) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Object not found',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Object retrieved successfully',
                'data' => $object,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve object',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getObjectImage($filename)
    {
        try {
            // Construct the full path
            $path = 'objects/' . $filename;

            // Check if file exists
            if (! Storage::disk('public')->exists($path)) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Image not found',
                ], 404);
            }

            // Get file content and mime type
            $file = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path);

            // Return image with proper headers
            return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'public, max-age=31536000');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
