<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="PHP CRM Gateway API",
 *     version="1.0.0",
 *     description="A Laravel API gateway that validates and publishes LeadCreated events to SQS with switchable logging",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * @OA\Server(
 *     url="http://localhost:8080",
 *     description="Local development server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format: Bearer {token}"
 * )
 */
class ApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api",
     *     summary="API Information",
     *     description="Get basic API information",
     *     operationId="getApiInfo",
     *     @OA\Response(
     *         response=200,
     *         description="API information",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="PHP CRM Gateway API"),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="description", type="string", example="A Laravel API gateway for lead management")
     *         )
     *     )
     * )
     */
    public function info()
    {
        return response()->json([
            'name' => 'PHP CRM Gateway API',
            'version' => '1.0.0',
            'description' => 'A Laravel API gateway for lead management with SQS integration',
        ]);
    }
}
