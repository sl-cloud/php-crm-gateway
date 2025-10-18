<?php

namespace App\Http\Controllers\Api;

use App\DTOs\LeadDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLeadRequest;
use App\Models\Lead;
use App\Services\Logging\LogManager;
use App\Contracts\MessagePublisherInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Leads",
 *     description="Lead management endpoints"
 * )
 */
class LeadController extends Controller
{
    private MessagePublisherInterface $messagePublisher;
    private LogManager $logManager;

    public function __construct(MessagePublisherInterface $messagePublisher, LogManager $logManager)
    {
        $this->messagePublisher = $messagePublisher;
        $this->logManager = $logManager;
    }

    /**
     * @OA\Post(
     *     path="/api/leads",
     *     summary="Create a new lead",
     *     description="Creates a new lead and publishes LeadCreated event to SQS",
     *     operationId="createLead",
     *     tags={"Leads"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="company", type="string", example="Acme Corp"),
     *             @OA\Property(property="source", type="string", enum={"website", "referral", "social", "email", "phone", "advertisement", "event", "other"}, example="website"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="utm_source", type="string", example="google"),
     *                 @OA\Property(property="utm_medium", type="string", example="cpc"),
     *                 @OA\Property(property="utm_campaign", type="string", example="summer-sale")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lead created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lead created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="lead_id", type="integer", example=1),
     *                 @OA\Property(property="correlation_id", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function store(CreateLeadRequest $request): JsonResponse
    {
        $correlationId = $request->input('correlation_id');
        $tenantId = $request->input('tenant_id');

        try {
            DB::beginTransaction();

            // Create LeadDTO from validated request
            $leadDTO = LeadDTO::fromArray($request->validated());

            // Save lead to database
            $lead = Lead::create($leadDTO->toDatabaseArray());

            // Publish LeadCreated event to SQS
            $messageData = $leadDTO->toSqsMessage();
            $messageData['lead_id'] = $lead->id;
            
            $published = $this->messagePublisher->publishLeadCreated($messageData);

            if (!$published) {
                throw new \Exception('Failed to publish LeadCreated event to SQS');
            }

            DB::commit();

            // Log successful lead creation
            $this->logManager->info('Lead created successfully', [
                'lead_id' => $lead->id,
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'email' => $lead->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully',
                'data' => [
                    'lead_id' => $lead->id,
                    'correlation_id' => $correlationId,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // Log error
            $this->logManager->error('Failed to create lead', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'request_data' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create lead: ' . $e->getMessage(),
            ], 500);
        }
    }
}
