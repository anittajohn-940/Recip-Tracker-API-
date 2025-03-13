<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        // Ensure the user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthorized. Please log in first.'
            ], 401); // 401 Unauthorized
        }

        $recipes = Recipe::all();

        if ($recipes->isEmpty()) {
            return response()->json([
                'message' => 'No recipes found'
            ], 404);
        }

        return response()->json([
            'message' => 'Recipes retrieved successfully',
            'data' => $recipes
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'ingredients' => 'required|string',
            'prep_time' => 'required|integer',
            'cook_time' => 'required|integer',
            'difficulty' => 'required|in:easy,medium,hard',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            $missingFields = collect($validator->errors()->messages())
                ->only(array_keys($validator->failed()))
                ->toArray();

            return response()->json([
                'message' => 'Required fields are missing!',
                'errors' => $missingFields
            ], 422);
        }

        $recipe = Recipe::create($request->all());

        return response()->json([
            'message' => 'Recipe created successfully!',
            'data' => $recipe
        ], 201);
    }
    public function show($id)
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json(['message' => 'Recipe not found'], 404);
        }

        return response()->json($recipe, 200);
    }
    public function update(Request $request, $id)
    {
        // Find the recipe
        $recipe = Recipe::find($id);
        if (!$recipe) {
            return response()->json(['message' => 'Recipe not found'], 404);
        }


        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'ingredients' => 'sometimes|string',
            'prep_time' => 'sometimes|integer',
            'cook_time' => 'sometimes|integer',
            'difficulty' => 'sometimes|in:easy,medium,hard',
            'description' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            $missingFields = collect($validator->errors()->messages())
                ->only(array_keys($validator->failed()))
                ->toArray();

            return response()->json([
                'message' => 'Required fields are missing!',
                'errors' => $missingFields
            ], 422);
        }

        // Update only provided fields
        $recipe->update($request->only([
            'name',
            'ingredients',
            'prep_time',
            'cook_time',
            'difficulty',
            'description'
        ]));

        return response()->json([
            'message' => 'Recipe updated successfully',
            'data' => $recipe
        ], 200);
    }


    public function destroy($id)
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json(['message' => 'Recipe not foundfffff'], 404);
        }

        $recipe->delete();

        return response()->json(['message' => 'Recipe deleted successfully'], 200);
    }

    public function filterByDifficulty($level)
    {
        if (!in_array($level, ['easy', 'medium', 'hard'])) {
            return response()->json(['message' => 'Invalid difficulty level. Choose from easy, medium, or hard.'], 400);
        }

        $recipes = Recipe::where('difficulty', $level)->get();

        if ($recipes->isEmpty()) {
            return response()->json(['message' => 'No recipes found for this difficulty level.'], 404);
        }

        return response()->json($recipes, 200);
    }
    public function advancedSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ingredients' => 'required|string',
            'time' => [
                'required',
                'string',
                'regex:/^\d+-\d+$/',
            ],
        ], [
            'ingredients.required' => 'Please provide at least one ingredient.',
            'ingredients.string' => 'Ingredients must be a comma-separated string.',
            'time.required' => 'Please specify a time range (e.g., 20-30).',
            'time.string' => 'Time range must be in string format.',
            'time.regex' => 'Invalid time format. Use min-max format (e.g., 20-30).',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ingredients = array_map('trim', explode(',', strtolower($request->input('ingredients'))));
        $timeRange = array_map('intval', explode('-', $request->input('time')));

        // Ensure valid time range
        if (count($timeRange) !== 2 || $timeRange[0] > $timeRange[1]) {
            return response()->json(['message' => 'Invalid time range. Make sure the first value is smaller than the second.'], 400);
        }

        $recipes = Recipe::where(function ($query) use ($ingredients) {
            foreach ($ingredients as $ingredient) {
                $query->orWhere('ingredients', 'like', "%$ingredient%");
            }
        })
            ->whereRaw('(prep_time + cook_time) BETWEEN ? AND ?', [$timeRange[0], $timeRange[1]])
            ->get();

        return $recipes->isEmpty()
            ? response()->json(['message' => 'No matching recipes found. Try adjusting your ingredients or time range.'], 404)
            : response()->json($recipes, 200);
    }
}